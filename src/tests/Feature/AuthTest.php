<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Laravel\Fortify\Features;
use App\Models\User;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Fortifyの機能をテスト中も確実に有効化
        config()->set('fortify.features', [
            Features::registration(),
            Features::emailVerification(),
        ]);
    }

    /** @test */
    public function 会員登録_未入力バリデーション()
    {
        $res = $this->post(route('register'), []);
        $res->assertSessionHasErrors(['name', 'email', 'password']);
    }

    /** @test */
    public function 会員登録_パスワード桁数と確認不一致()
    {
        $res = $this->post(route('register'), [
            'name' => '太郎',
            'email' => 'taro@example.com',
            'password' => 'short',
            'password_confirmation' => 'mismatch',
        ]);

        $res->assertSessionHasErrors(['password']);
    }

    /** @test */
    public function 会員登録_成功でメール認証画面へ遷移()
    {
        Notification::fake();

        $res = $this->post(route('register'), [
            'name' => '太郎',
            'email' => 'taro@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // カスタム RegisterResponse が verify.prompt にリダイレクトする想定
        $res->assertRedirect(route('verify.prompt'));

        $this->assertDatabaseHas('users', ['email' => 'taro@example.com']);

        $user = User::where('email', 'taro@example.com')->first();
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /** @test */
    public function ログイン_未入力バリデーション()
    {
        $res = $this->post(route('login'), []);
        $res->assertSessionHasErrors(['email', 'password']);
    }

    /** @test */
    public function ログイン_間違いでエラー()
    {
        $res = $this->post(route('login'), [
            'email' => 'none@example.com',
            'password' => 'wrong',
        ]);

        // 認証失敗時は何らかのエラー（auth.failed 等）がセッションに入る
        $res->assertSessionHasErrors();
    }

    /** @test */
    public function ログイン_初回はメール認証案内へ()
    {
        // 既にメール認証は完了しているが、初回ログイン誘導はまだ
        $user = User::factory()->create([
            'password' => bcrypt('secret123'),
            'email_verified_at' => now(),
            'first_login_redirected_at' => null,
        ]);

        $res = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        // LoginResponse が first_login_redirected_at を埋めつつ verify.prompt へ
        $res->assertRedirect(route('verify.prompt'));
        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function ログイン_2回目_成功でホームへ()
    {
        // 初回誘導は済んでいる想定
        $user = User::factory()->create([
            'password' => bcrypt('secret123'),
            'email_verified_at' => now(),
            'first_login_redirected_at' => now(),
        ]);

        $res = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        // fortify.home が未設定でも /（= items.index）にフォールバック
        $res->assertRedirect(route('items.index'));
        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function ログアウトできる()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        $res = $this->post(route('logout'));

        // 通常はトップへ（= items.index = "/"）
        $res->assertRedirect('/');
        $this->assertGuest();
    }
}
