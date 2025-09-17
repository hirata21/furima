<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management</title>

    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    @yield('css')
</head>

<body>
    @php
    // ログイン/会員登録系のページかどうか
    // 既存のあなたのルート名（auth.login.form / auth.register.form）と
    // Laravel標準（login / register）両方に対応
    $isAuthPage = \Illuminate\Support\Facades\Route::is(
    'auth.login.form',
    'auth.register.form',
    'login',
    'register',
    'verification.notice',
    'verify.prompt'
    );
    @endphp

    <header class="header">
        <div class="header__logo">
            <a href="{{ route('items.index') }}" aria-label="トップへ">
                <img src="{{ asset('images/logo.svg') }}" alt="ロゴ" class="logo-image">
            </a>
        </div>

        @unless($isAuthPage)
        {{-- 検索フォーム（認証ページ以外で表示） --}}
        <form class="search-form" action="{{ route('items.index') }}" method="GET" role="search" aria-label="商品検索">
            <input
                type="text"
                name="keyword"
                value="{{ request('keyword') }}"
                class="search-input"
                placeholder="なにをお探しですか？"
                autocomplete="off">
            @if(request()->has('tab'))
            <input type="hidden" name="tab" value="{{ request('tab') }}">
            @endif
        </form>

        {{-- 右側リンク・ボタン（認証ページ以外で表示） --}}
        <nav class="header-right" aria-label="ユーザーナビゲーション">
            @auth
            <a href="{{ route('logout') }}" class="header-link"
                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                ログアウト
            </a>
            <a href="{{ route('mypage') }}" class="header-link">マイページ</a>
            <a href="{{ route('items.create') }}" class="header-button sell">出品</a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">
                @csrf
            </form>
            @else
            <a href="{{ route('auth.login.form') }}" class="header-link">ログイン</a>
            <a href="{{ route('mypage') }}" class="header-link">マイページ</a>
            <a href="{{ route('items.create') }}" class="header-button sell">出品</a>
            @endauth
        </nav>
        @endunless
    </header>

    <main>
        @yield('content')
    </main>

    @stack('scripts')
</body>

</html>