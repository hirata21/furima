<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Purchase;
use App\Models\UserAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent as StripePaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook as StripeWebhook;

final class StripeWebhookController extends Controller
{
    /** metadata を安全に配列化 */
    private function metaToArray(mixed $meta): array
    {
        if (!$meta) return [];
        if (is_array($meta)) return $meta;
        if (is_object($meta) && method_exists($meta, 'toArray')) {
            /** @var array $arr */
            $arr = $meta->toArray();
            return $arr;
        }
        // stdClass 等
        if (is_object($meta)) {
            return (array) $meta;
        }
        return [];
    }

    public function handle(Request $request): JsonResponse
    {
        $payload   = $request->getContent(); // ★ Raw body（署名検証に必須）
        $sigHeader = (string) $request->header('Stripe-Signature', '');
        $whsec     = (string) (config('services.stripe.webhook_secret') ?: env('STRIPE_WEBHOOK_SECRET', ''));

        if ($whsec === '') {
            Log::error('STRIPE_WEBHOOK_SECRET is missing');
            return response()->json(['error' => 'server misconfigured'], 500);
        }

        try {
            $event = StripeWebhook::constructEvent($payload, $sigHeader, $whsec);
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature verify failed', ['msg' => $e->getMessage()]);
            return response()->json(['error' => 'invalid signature'], 400);
        } catch (\UnexpectedValueException $e) {
            Log::warning('Stripe webhook invalid payload', ['msg' => $e->getMessage()]);
            return response()->json(['error' => 'invalid payload'], 400);
        }

        Log::info('Stripe webhook received', [
            'event_id' => $event->id ?? null,
            'type'     => $event->type ?? null,
        ]);

        // ★ 冪等性：同じイベントIDの再送はスキップ
        if (!empty($event->id)) {
            $cacheKey = 'stripe_event_' . $event->id;
            if (!Cache::add($cacheKey, true, now()->addMinutes(15))) {
                Log::info('Stripe event skipped (duplicate)', ['event_id' => $event->id, 'type' => $event->type]);
                return response()->json(['skip' => 'duplicate'], 200);
            }
        }

        switch ($event->type) {
            // 非同期決済（コンビニ等）の成功は基本これ
            case 'checkout.session.async_payment_succeeded':
                $this->confirmPaidBySession($event->data->object);
                break;

            // 同期決済（カード等）は completed 直後に paid なら確定
            case 'checkout.session.completed': {
                    $session = $event->data->object;
                    if (($session->payment_status ?? '') === 'paid') {
                        $this->confirmPaidBySession($session);
                    }
                    break;
                }

                // PaymentIntent 側からも保険をかける
            case 'payment_intent.succeeded':
                $this->confirmPaidByPaymentIntent($event->data->object);
                break;

            // 一部の支払い手段では charge から辿る必要がある場合の保険
            case 'charge.succeeded':
                $this->confirmPaidByCharge($event->data->object);
                break;

            default:
                Log::info('Stripe event ignored', ['type' => $event->type]);
        }

        return response()->json(['received' => true], 200);
    }

    /** checkout.session から支払い確定を反映 */
    private function confirmPaidBySession(object $session): void
    {
        $meta   = $this->metaToArray($session->metadata ?? []);
        $itemId = (int)($meta['item_id'] ?? $meta['item'] ?? 0);
        $userId = (int)($meta['user_id'] ?? $meta['buyer_id'] ?? 0);
        $pmType = (string)($meta['payment_method'] ?? ($session->payment_method_types[0] ?? 'unknown'));

        // 不足時は PaymentIntent を参照（Checkout でよくある）
        if ((!$itemId || !$userId) && !empty($session->payment_intent)) {
            $pi = $this->retrievePaymentIntent((string) $session->payment_intent);
            if ($pi) {
                $m2    = $this->metaToArray($pi->metadata ?? []);
                $itemId = $itemId ?: (int)($m2['item_id'] ?? $m2['item'] ?? 0);
                $userId = $userId ?: (int)($m2['user_id'] ?? $m2['buyer_id'] ?? 0);
                $pmType = $pmType ?: (string)($m2['payment_method'] ?? ($pi->payment_method_types[0] ?? 'unknown'));
            } else {
                // 最後の手段として client_reference_id を itemId とみなす設計もあり
                $itemId = $itemId ?: (int)($session->client_reference_id ?? 0);
            }
        }

        $this->updateDbAsPaid($itemId, $userId, null, (string)($session->id ?? ''), $pmType);
    }

    /** PaymentIntent から支払い確定を反映（保険） */
    private function confirmPaidByPaymentIntent(object $pi): void
    {
        $meta   = $this->metaToArray($pi->metadata ?? []);
        $itemId = (int)($meta['item_id'] ?? $meta['item'] ?? 0);
        $userId = (int)($meta['user_id'] ?? $meta['buyer_id'] ?? 0);
        $pmType = (string)($meta['payment_method'] ?? ($pi->payment_method_types[0] ?? 'unknown'));

        $this->updateDbAsPaid($itemId, $userId, (string)($pi->id ?? ''), null, $pmType);
    }

    /** Charge から PaymentIntent を辿って確定（さらに保険） */
    private function confirmPaidByCharge(object $charge): void
    {
        $piId = (string)($charge->payment_intent ?? '');
        if (!$piId) return;

        $pi = $this->retrievePaymentIntent($piId);
        if (!$pi) return;

        $meta   = $this->metaToArray($pi->metadata ?? []);
        $itemId = (int)($meta['item_id'] ?? $meta['item'] ?? 0);
        $userId = (int)($meta['user_id'] ?? $meta['buyer_id'] ?? 0);
        $pmType = (string)($meta['payment_method'] ?? ($pi->payment_method_types[0] ?? 'unknown'));

        $this->updateDbAsPaid($itemId, $userId, $piId, null, $pmType);
    }

    /** PaymentIntent を秘鍵で取得（秘鍵未設定なら null） */
    private function retrievePaymentIntent(string $piId): ?object
    {
        $secret = (string) (config('services.stripe.secret') ?: env('STRIPE_SECRET', ''));
        if ($secret === '') return null;

        Stripe::setApiKey($secret);
        try {
            return StripePaymentIntent::retrieve($piId);
        } catch (\Throwable $e) {
            Log::warning('Retrieve PaymentIntent failed', ['pi' => $piId, 'err' => $e->getMessage()]);
            return null;
        }
    }

    /** DB更新（SOLD 化 & 購入スナップショット保存） */
    private function updateDbAsPaid(int $itemId, int $userId, ?string $piId, ?string $sessionId, string $pmType): void
    {
        if (!$itemId || !$userId) {
            Log::warning('Stripe paid but missing ids', compact('itemId', 'userId', 'piId', 'sessionId'));
            return;
        }

        DB::transaction(function () use ($itemId, $userId, $pmType): void {
            /** @var Item|null $item */
            $item = Item::lockForUpdate()->find($itemId);
            if (!$item) {
                Log::warning('Item not found for payment', compact('itemId'));
                return;
            }

            // 住所スナップショット（購入時点の最新を使用）
            $ua = UserAddress::where('user_id', $userId)->latest('id')->first();
            if (!$ua || !$ua->postcode || !$ua->address) {
                // 住所が無いなら SOLD せず警告（要件に合わせる）
                Log::warning('Paid but user address missing', ['userId' => $userId, 'itemId' => $itemId]);
                return;
            }

            // 既に SOLD なら冪等的に何もしない
            if (!$item->is_sold) {
                $item->is_sold = 1;
                $item->save();
            }

            // 購入テーブルへスナップショット保存（存在すれば更新）
            $payload = [
                'user_id'         => $userId,
                'payment_method'  => $pmType,
                'user_address_id' => $ua->id,
                'postcode'        => (string) $ua->postcode,
                'address'         => (string) $ua->address,
                'building'        => $ua->building,
            ];

            Purchase::unguarded(function () use ($item, $payload): void {
                Purchase::updateOrCreate(['item_id' => $item->id], $payload);
            });
        });

        Log::info('Stripe paid reflected', compact('itemId', 'userId', 'piId', 'sessionId', 'pmType'));
    }
}
