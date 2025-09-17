<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;

class AuthController extends Controller
{
    /** 登録フォーム */
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    /** 登録処理（FormRequest 使用） */
    public function register(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // メール認証メール送信（User が MustVerifyEmail を実装している前提）
        event(new Registered($user));

        // 自動ログイン & セッション再生成
        Auth::login($user);
        $request->session()->regenerate();

        // 未認証なら誘導ページへ、認証済みならホームへ
        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verify.prompt');
        }

        // プロフィール初回設定に飛ばしたいならこちらに変更
        // return redirect()->route('profile.create');

        return redirect()->intended(config('fortify.home', '/'));
    }

    /** ログインフォーム */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /** ログイン処理（FormRequest 使用） */
    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');
        $remember    = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            $user = $request->user();
            if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
                return redirect()->route('verify.prompt');
            }

            return redirect()->intended(config('fortify.home', '/'));
        }

        return back()
            ->withErrors(['email' => __('auth.failed')])
            ->withInput();
    }

    /** ログアウト */
    public function logout(): RedirectResponse
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login'); // または items.index など
    }
}
