<?php

namespace App\Actions\Fortify;

use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticateUser
{
    public function __invoke(Request $request)
    {
        // LoginRequest を使ってバリデーション
        $loginRequest = app(LoginRequest::class);
        $loginRequest->merge($request->all());
        $loginRequest->setMethod('POST');
        app()->call([$loginRequest, 'validateResolved']);

        if (Auth::attempt($loginRequest->only('email', 'password'), $loginRequest->filled('remember'))) {
            $request->session()->regenerate();
            return Auth::user(); // ✅ Fortifyがこれを受け取ってログイン後処理をする
        }

        return null; // Fortifyが自動でエラー処理を行う
    }
}