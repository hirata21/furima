<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Item;

class LikesTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function いいね_ログインでトグルできる()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();

        $this->actingAs($user);

        $res = $this->post(route('like.toggle', $item->id)); // ★調整：ルート名
        $res->assertRedirect();

        $this->assertTrue($user->fresh()->likes->contains($item->id));

        // 再度押すと解除
        $this->post(route('like.toggle', $item->id));
        $this->assertFalse($user->fresh()->likes->contains($item->id));
    }

    /** @test */
    public function マイリスト一覧_いいねした品のみ_SOLDも表示_未認証では非表示()
    {
        $u = User::factory()->create();
        $i1 = Item::factory()->create(['name' => 'LIKE対象', 'is_sold' => 1]);
        $i2 = Item::factory()->create(['name' => '対象外', 'is_sold' => 0]);

        $u->likes()->attach($i1->id);

        // ログイン時
        $this->actingAs($u);
        $res = $this->get(route('items.index', ['tab' => 'likes'])); // ★調整：マイリストタブ
        $res->assertOk();
        $res->assertSee('LIKE対象');
        $res->assertSee('SOLD');
        $res->assertDontSee('対象外');

        // 未ログインだと非表示
        auth()->logout();
        $res2 = $this->get(route('items.index', ['tab' => 'likes']));
        $res2->assertOk();
        $res2->assertDontSee('LIKE対象');
    }
}
