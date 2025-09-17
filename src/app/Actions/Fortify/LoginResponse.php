<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        /** @var \App\Models\User $user */
        $user = User::query()->findOrFail($request->user()->id);

        $isFirstLogin = is_null($user->first_login_redirected_at);

        // ★ 初回ログインは認証済み/未認証に関係なく必ず誘導
        if ($isFirstLogin) {
            // forceFill を使う場合
            $user->forceFill(['first_login_redirected_at' => now()])->save();

            return redirect()->route('verify.prompt');
        }

        // 2回目以降は通常遷移
        return redirect()->intended(config('fortify.home', '/'));
    }
}