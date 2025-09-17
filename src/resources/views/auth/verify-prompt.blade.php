@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/verify_prompt.css') }}">
@endsection

@section('content')
<div class="verify-page">
    <div class="verify-card">
        <p class="verify-lead">
            登録していただいたメールアドレスに認証メールを送付しました。<br>
            メール認証を完了してください。
        </p>

        {{-- 上：認証へ（Fortify の /email/verify） --}}
        <a href="{{ route('verify.mailhog') }}" class="btn-primary">認証はこちらから</a>

        {{-- 下：青文字のテキスト形式（POSTで再送） --}}
        <form method="POST" action="{{ route('verification.send') }}" class="resend-form">
            @csrf
            <button type="submit" class="link-like">認証メールを再送する</button>
        </form>
    </div>
</div>
@endsection