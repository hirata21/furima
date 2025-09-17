@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/register.css') }}">
@endsection

@section('content')
<div class="register-wrapper">
    <div class="register-container">
        <h2>会員登録</h2>

        @if(session('success'))
        <div class="success">{{ session('success') }}</div>
        @endif

        {{-- 認証ルートへ POST --}}
        <form method="POST" action="{{ route('register') }}">
            @csrf

            <label for="name">ユーザー名</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}">
            @error('name')
            <div class="error">{{ $message }}</div>
            @enderror

            <label for="email">メールアドレス</label>
            <input type="text" id="email" name="email" value="{{ old('email') }}">
            @error('email')
            <div class="error">{{ $message }}</div>
            @enderror

            <label for="password">パスワード</label>
            <input type="password" id="password" name="password">
            @error('password')
            <div class="error">{{ $message }}</div>
            @enderror

            <label for="password_confirmation">確認用パスワード</label>
            <input type="password" id="password_confirmation" name="password_confirmation">
            @error('password_confirmation')
            <div class="error">{{ $message }}</div>
            @enderror

            <button type="submit" class="register-button">登録する</button>
        </form>

        {{-- ログイン導線（アプリのルート設計に合わせて） --}}
        <p class="login-link">
            <a href="{{ route('auth.login.form') }}">ログインはこちら</a>
        </p>
    </div>
</div>
@endsection