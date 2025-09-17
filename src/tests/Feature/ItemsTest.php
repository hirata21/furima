<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Item;
use App\Models\Category;

class ItemsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 商品一覧_全商品取得_自分の出品は非表示_SOLD表示()
    {
        // 自分＆他人
        $me    = User::factory()->create(['email_verified_at' => now()]);
        $other = User::factory()->create();

        // 自分の出品（出ない想定）
        Item::factory()->create([
            'user_id' => $me->id,
            'name'    => '自分の品',
            'is_sold' => 0,
        ]);

        // 他人の売済・未売
        Item::factory()->create([
            'user_id' => $other->id,
            'name'    => '売済品',
            'is_sold' => 1,
        ]);
        Item::factory()->create([
            'user_id' => $other->id,
            'name'    => '未売品',
            'is_sold' => 0,
        ]);

        $this->actingAs($me);

        // ★ “おすすめ” タブを明示（コントローラの既定タブの影響を受けない）
        $res = $this->withSession(['preferred_tab' => 'recommend'])
            ->get(route('items.index'));

        $res->assertOk();
        $res->assertSee('売済品');
        $res->assertSee('SOLD');       // 一覧で SOLD バッジを出している前提
        $res->assertDontSee('自分の品'); // 自分の出品は除外
        $res->assertSee('未売品');
    }

    /** @test */
    public function 商品検索_商品名の部分一致()
    {
        Item::factory()->create(['name' => 'NIKE AIR']);
        Item::factory()->create(['name' => 'adidas Tee']);

        // ★ 検索時も“おすすめ”に固定しておくと安定
        $res = $this->withSession(['preferred_tab' => 'recommend'])
            ->get(route('items.index', ['keyword' => 'air']));

        $res->assertOk();
        $res->assertSee('NIKE AIR');
        $res->assertDontSee('adidas Tee');
    }

    /** @test */
    public function 商品詳細_必要情報が出る_カテゴリ複数も表示()
    {
        $item = Item::factory()->create([
            'name'        => 'バッグ',
            'brand'       => 'GU',
            'price'       => 5000,
            'description' => '説明',
            'condition'   => '良好',
        ]);

        $c1 = Category::factory()->create(['name' => 'レザー']);
        $c2 = Category::factory()->create(['name' => '限定']);

        // 多対多
        $item->categories()->attach([$c1->id, $c2->id]);

        $res = $this->get(route('items.show', $item->id));
        $res->assertOk();
        $res->assertSee('バッグ');
        $res->assertSee('GU');
        $res->assertSee('¥');
        $res->assertSee('説明');
        $res->assertSee('良好');
        $res->assertSee('レザー');
        $res->assertSee('限定');
    }
}