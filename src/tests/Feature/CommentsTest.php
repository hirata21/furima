<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Item;

class CommentsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 未ログインはコメント不可()
    {
        $seller = User::factory()->create();                 // ← items.user_id を満たす
        $item   = Item::factory()->create(['user_id' => $seller->id]);

        $res = $this->post(route('comments.store', $item->id), ['comment' => 'テスト']);

        // 未ログインは login へ（/login でOK。カスタム名でも URI が /login なら通る）
        $res->assertRedirect('/login');
    }

    /** @test */
    public function コメント_未入力_文字数オーバー_でバリデ()
    {
        $user   = User::factory()->create();
        $seller = User::factory()->create();
        $item   = Item::factory()->create(['user_id' => $seller->id]);

        $this->actingAs($user);

        $this->post(route('comments.store', $item->id), ['comment' => ''])
            ->assertSessionHasErrors('comment');

        $this->post(route('comments.store', $item->id), ['comment' => str_repeat('あ', 256)])
            ->assertSessionHasErrors('comment');
    }

    /** @test */
    public function コメント_成功で保存_詳細へ戻る()
    {
        $user   = User::factory()->create();
        $seller = User::factory()->create();
        $item   = Item::factory()->create(['user_id' => $seller->id]);

        $this->actingAs($user);

        $res = $this->post(route('comments.store', $item->id), ['comment' => 'ナイス！']);

        // withFragment('comments') を使っている前提
        $res->assertRedirect(route('items.show', $item->id) . '#comments');

        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'item_id' => $item->id,
            'content' => 'ナイス！',   // ← DBカラムは content
        ]);
    }
}
