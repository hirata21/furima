<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\UserAddress;

class PurchaseAddressTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 配送先変更_バリデーションと保存_購入画面へ反映()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create(['is_sold' => 0]);

        $this->actingAs($user);

        // バリデーション（郵便番号NG & 住所未入力）
        $this->post(route('purchase.address.update', $item->id), [
            'postcode' => '1234567',
            'address'  => '',
        ])->assertSessionHasErrors(['postcode', 'address']);

        // 正常保存
        $this->post(route('purchase.address.update', $item->id), [
            'postcode' => '123-4567',
            'address'  => '東京都新宿区1-1-1',
            'building' => 'テストビル',
        ])->assertRedirect(route('purchase.show', $item->id));

        $this->assertDatabaseHas('user_addresses', [
            'user_id'  => $user->id,
            'postcode' => '123-4567',
            'address'  => '東京都新宿区1-1-1',
            'building' => 'テストビル',
        ]);

        // 画面反映確認
        $res = $this->get(route('purchase.show', $item->id));
        $res->assertOk();
        $res->assertSee('123-4567');
        $res->assertSee('東京都新宿区1-1-1');
        $res->assertSee('テストビル');
    }

    /** @test */
    public function 購入完了状態_一覧でsold_マイページ購入一覧に出る()
    {
        $buyer = User::factory()->create();
        $item  = Item::factory()->create([
            'is_sold' => 0,
            'name'    => '購入ターゲット',
        ]);
        $addr  = UserAddress::factory()->create(['user_id' => $buyer->id]);

        // Stripe を通さず DB で購入確定状態を再現
        $item->update(['is_sold' => 1]);
        Purchase::create([
            'user_id'         => $buyer->id,
            'item_id'         => $item->id,
            'payment_method'  => 'card', // or 'konbini'
            'user_address_id' => $addr->id,
            'postcode'        => $addr->postcode,
            'address'         => $addr->address,
            'building'        => $addr->building,
        ]);

        $this->actingAs($buyer);

        // items.index は ?tab=recommend 指定時に / へ 302 正規化する仕様なので追随する
        $res = $this->followingRedirects()
                    ->get(route('items.index', ['tab' => 'recommend']));
        $res->assertOk();
        $res->assertSee('SOLD');
        $res->assertSee('購入ターゲット');

        // マイページ「購入した商品」タブにも表示される
        $res2 = $this->get(route('mypage', ['tab' => 'buy']));
        $res2->assertOk();
        $res2->assertSee('購入ターゲット');
        $res2->assertSee('SOLD');
    }
}