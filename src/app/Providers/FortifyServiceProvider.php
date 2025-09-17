<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use App\Actions\Fortify\RegisterResponse as CustomRegisterResponse;
use Laravel\Fortify\Contracts\LoginResponse    as LoginResponseContract;
use App\Actions\Fortify\LoginResponse        as CustomLoginResponse;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // これで Fortify の登録直後のリダイレクト先を上書き
        $this->app->singleton(RegisterResponseContract::class, CustomRegisterResponse::class);

        // ★ 追加：ログイン直後のリダイレクト（初回だけ認証待ちへ）
        $this->app->singleton(LoginResponseContract::class, CustomLoginResponse::class);
    }

    public function boot(): void
    {
        // 登録は CreateNewUser 内で RegisterRequest の rules/messages を使用
        Fortify::createUsersUsing(CreateNewUser::class);

        // ★ authenticateThrough は使わない（ミドルウェア方式のため）
        // Fortify::authenticateThrough(...) は書かない

        // ★ 認証本体（成功: user / 失敗: null）※ここで view/redirect は返さない
        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('email', $request->email)->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {
                return null; // → Fortify が back(302) + 'auth.failed'
            }
            return $user;
        });

        // Fortify の GET ビューは無効化（configで views=false）するので
        // Fortify::loginView()/registerView() の設定は不要

        // レート制限
        RateLimiter::for('login', fn(Request $r) => Limit::perMinute(10)->by((string)$r->email . $r->ip()));
        RateLimiter::for('two-factor', fn(Request $r) => Limit::perMinute(10)->by((string)$r->session()->get('login.id')));
    }
}
