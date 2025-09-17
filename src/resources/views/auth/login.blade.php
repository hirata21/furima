@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/login.css') }}">
@endsection

@section('content')
<div class="login-wrapper">
    <div class="login-container">
        <h2>ログイン</h2>


        <form method="POST" action="{{ route('login') }}" novalidate>
            @csrf

            <label for="email">メールアドレス</label>
            <input type="text" id="email" name="email" value="{{ old('email') }}" autocomplete="email" autofocus>
            @error('email')
            <div class="error">{{ $message }}</div>
            @enderror

            <label for="password">パスワード</label>
            <input
                type="password"
                id="password"
                name="password"
                autocomplete="current-password">
            @error('password')
            <div class="error">{{ $message }}</div>
            @enderror

            <button type="submit" class="login-button">ログインする</button>
        </form>

        <p class="register-link">
            <a href="{{ route('auth.register.form') }}">会員登録はこちら</a>
        </p>
    </div>
</div>
@endsection