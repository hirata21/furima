<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\UserAddress;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Http\Requests\AddressRequest;
use App\Http\Requests\PurchaseRequest;
use Illuminate\Support\Facades\Log; // ← 追加

class PurchaseController extends Controller
{
    public function show(Request $request, Item $item)
    {
        $address = Auth::user()->address;
        return view('purchase.show', compact('item', 'address'));
    }

    public function editAddress(Item $item)
    {
        $address = auth()->user()->address;
        return view('purchase.address_edit', compact('item', 'address'));
    }

    public function updateAddress(AddressRequest $request, Item $item)
    {
        $user = auth()->user();
        $data = $request->validated();
        $building = $request->input('building');

        UserAddress::updateOrCreate(
            ['user_id' => $user->id],
            [
                'postcode' => $data['postcode'],
                'address'  => $data['address'],
                'building' => ($building !== null && $building !== '') ? $building : null,
            ]
        );

        return redirect()
            ->route('purchase.show', $item->id)
            ->with('success', '住所を更新しました。');
    }

    public function store(PurchaseRequest $request, Item $item)
    {
        if ($item->is_sold) {
            return redirect()->route('items.index')->with('error', 'すでに購入されています。');
        }

        $data = $request->validate([
            'payment_method' => ['required', 'in:クレジットカード,コンビニ払い'],
        ]);

        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        $pmType = $data['payment_method'] === 'コンビニ払い' ? 'konbini' : 'card';
        $amount = max(120, (int) $item->price);

        $metadata = [
            'item_id'        => (string) $item->id,
            'user_id'        => (string) Auth::id(),
            'payment_method' => $pmType,
        ];

        $baseUrl = rtrim(config('app.url'), '/');

        $payload = [
            'mode' => 'payment',
            'payment_method_types' => [$pmType],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'jpy',
                    'product_data' => ['name' => $item->name ?: '商品'],
                    'unit_amount' => $amount,
                ],
                'quantity' => 1,
            ]],
            'metadata' => $metadata,
            'payment_intent_data' => [
                'metadata' => $metadata,
            ],
            'success_url' => $baseUrl . route('purchase.success', $item->id, false) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => $baseUrl . route('purchase.cancel',  $item->id, false),
            'client_reference_id' => (string)$item->id,
        ];

        // 顧客メール（値は保存しない/存在フラグのみログ）
        $email = Auth::user()->email ?? null;
        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $payload['customer_email'] = $email;
        }

        // ---- ここから：ログは環境ガード付きで info を出す ----
        if (app()->isLocal() || app()->environment('staging')) {
            Log::info('checkout payload', [
                'payment_method_types' => $payload['payment_method_types'],
                'has_customer_email'   => isset($payload['customer_email']),
                'amount'               => $amount,
                'metadata'             => [
                    'item_id' => $metadata['item_id'],
                    'user_id' => $metadata['user_id'],
                    'payment_method' => $metadata['payment_method'],
                ],
            ]);
        }
        // -------------------------------------------------------

        try {
            $checkoutSession = \Stripe\Checkout\Session::create($payload);

            // 追加のメタ確認ログ（開発/ステージングのみ）
            if (app()->isLocal() || app()->environment('staging')) {
                $check = \Stripe\Checkout\Session::retrieve([
                    'id' => $checkoutSession->id,
                    'expand' => ['payment_intent'],
                ]);

                $sm = $check->metadata ?? null;
                $pi = $check->payment_intent ?? null;
                $piMeta = $pi && $pi->metadata ? $pi->metadata : null;

                Log::info('checkout meta check', [
                    'session_id'       => $check->id,
                    'session_metadata' => [
                        'item_id'        => $sm->item_id        ?? null,
                        'user_id'        => $sm->user_id        ?? null,
                        'payment_method' => $sm->payment_method ?? null,
                    ],
                    'pi_id'       => $pi->id ?? null,
                    'pi_metadata' => $piMeta ? [
                        'item_id' => $piMeta->item_id ?? null,
                        'user_id' => $piMeta->user_id ?? null,
                    ] : [],
                ]);

                Log::info('checkout session created', [
                    'id'                   => $checkoutSession->id,
                    'payment_method_types' => $checkoutSession->payment_method_types,
                    'success_url'          => $checkoutSession->success_url,
                    'cancel_url'           => $checkoutSession->cancel_url,
                ]);
            }

            return redirect()->away($checkoutSession->url);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            // エラーは本番でも常に残す
            Log::error('Stripe API error', [
                'message' => $e->getMessage(),
                'type'    => method_exists($e, 'getError') && $e->getError() ? $e->getError()->type  : null,
                'param'   => method_exists($e, 'getError') && $e->getError() ? $e->getError()->param : null,
            ]);
            $msg = app()->isLocal() ? $e->getMessage() : '決済セッションの作成に失敗しました。時間をおいて再度お試しください。';
            return back()->with('error', $msg);
        } catch (\Throwable $e) {
            Log::error('Stripe unknown error', ['message' => $e->getMessage()]);
            return back()->with('error', '決済セッションの作成に失敗しました。');
        }
    }

    public function success(Request $request, Item $item)
    {
        $sessionId = $request->query('session_id');
        if (!$sessionId) {
            return redirect()->route('items.index')->with('error', 'セッションIDが見つかりません。');
        }

        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $session = \Stripe\Checkout\Session::retrieve([
                'id' => $sessionId,
                'expand' => ['payment_intent'],
            ]);

            $paid = ($session->payment_status === 'paid')
                || ($session->payment_intent && $session->payment_intent->status === 'succeeded');

            if (!$paid) {
                return redirect()->route('purchase.show', $item->id)
                    ->with('error', '支払いが未完了です。コンビニ払いは入金完了後に反映されます。');
            }

            // 決済からID類を決定
            $metaItemId   = (int)($session->metadata->item_id ?? 0);
            $clientRefId  = (int)($session->client_reference_id ?? 0);
            $targetItemId = $metaItemId ?: ($clientRefId ?: (int)$item->id);

            $buyerId = (int)($session->metadata->user_id ?? Auth::id());
            $pmType  = (string)($session->metadata->payment_method
                ?? ($session->payment_method_types[0] ?? 'card'));

            DB::transaction(function () use ($targetItemId, $buyerId, $pmType) {

                // 1) 商品を売却済みに（ロック＆更新）
                $it = Item::lockForUpdate()->findOrFail($targetItemId);
                if (!$it->is_sold) {
                    $it->forceFill(['is_sold' => 1])->save();
                }

                // 2) ユーザーの最新住所（必須ならチェック）
                $ua = UserAddress::where('user_id', $buyerId)->latest('id')->first();

                // 3) purchases 保存：存在するカラムだけ詰める（Unknown column対策）
                $payload = [
                    'item_id' => $it->id,
                    'user_id' => $buyerId,
                ];
                if (Schema::hasColumn('purchases', 'payment_method')) {
                    $payload['payment_method'] = $pmType ?: 'unknown';
                }
                if ($ua) {
                    if (Schema::hasColumn('purchases', 'user_address_id')) {
                        $payload['user_address_id'] = $ua->id;
                    }
                    if (Schema::hasColumn('purchases', 'postcode')) {
                        $payload['postcode'] = (string)$ua->postcode;
                    }
                    if (Schema::hasColumn('purchases', 'address')) {
                        $payload['address'] = (string)$ua->address;
                    }
                    if (Schema::hasColumn('purchases', 'building')) {
                        $payload['building'] = $ua->building;
                    }
                }

                // 既存あれば更新、無ければ新規
                $purchase = Purchase::where('item_id', $it->id)->lockForUpdate()->first();
                if ($purchase) {
                    $purchase->forceFill($payload)->save();
                } else {
                    $purchase = new Purchase();
                    $purchase->forceFill($payload)->save();
                }
            });

            return redirect()->route('items.index')->with('success', '購入が確定しました。');
        } catch (\Throwable $e) {
            // エラーは本番でも常に残す
            Log::error('Stripe success handle error', ['message' => $e->getMessage()]);
            return redirect()->route('items.index')->with('error', '決済の検証に失敗しました。');
        }
    }

    public function cancel(Request $request, Item $item)
    {
        return redirect()->route('purchase.show', $item->id)->with('error', '決済がキャンセルされました。');
    }
}
