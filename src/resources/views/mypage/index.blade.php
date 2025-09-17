@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/mypage/index.css') }}">
@endsection

@section('content')
<main class="mypage">

    <section class="mypage__profile profile">
        <img
            src="{{ $user->profile_image ? asset('storage/' . $user->profile_image) : '' }}"
            class="profile__image {{ $user->profile_image ? '' : 'profile__image--default' }}"
            alt="">
        <div class="profile__name">{{ $user->name }}</div>
        <a href="{{ route('profile.create') }}" class="profile__edit-button">プロフィールを編集</a>
    </section>

    <nav class="mypage__tabs tabs" aria-label="マイページのタブ">
        <ul class="tabs__list" role="tablist">
            <li class="tabs__item" role="presentation">
                <a
                    href="{{ route('mypage', ['tab' => 'sell']) }}"
                    role="tab"
                    aria-selected="{{ $tab === 'sell' ? 'true' : 'false' }}"
                    class="tabs__link {{ $tab === 'sell' ? 'is-active' : '' }}">出品した商品</a>
            </li>
            <li class="tabs__item" role="presentation">
                <a
                    href="{{ route('mypage', ['tab' => 'buy']) }}"
                    role="tab"
                    aria-selected="{{ $tab === 'buy' ? 'true' : 'false' }}"
                    class="tabs__link {{ $tab === 'buy' ? 'is-active' : '' }}">購入した商品</a>
            </li>
        </ul>
    </nav>

    <section class="mypage__items">
        <h2 class="visually-hidden">商品一覧</h2>

        @forelse ($items as $item)
        <ul class="item-list">
            @break
        </ul>
        @empty
        <p class="item-list__empty">
            {{ $tab === 'buy' ? '購入した商品はまだありません。' : '出品した商品はまだありません。' }}
        </p>
        @endforelse

        @if($items->isNotEmpty())
        <ul class="item-list item-list--xl" aria-live="polite">
            @foreach ($items as $item)
            <li class="item-card">
                <figure class="item-card__figure">
                    <div class="item-card__thumb">
                        <img
                            src="{{ asset('storage/' . $item->image_path) }}"
                            class="item-card__image"
                            alt="{{ $item->name }} の商品画像">
                        @if ($tab === 'buy' || (!empty($item->is_sold) && $item->is_sold))
                        <span class="item-card__badge item-card__badge--sold" aria-label="売り切れ">SOLD</span>
                        @endif
                    </div>
                    <figcaption class="item-card__name">{{ $item->name }}</figcaption>
                </figure>
            </li>
            @endforeach
        </ul>
        @endif
    </section>
</main>
@endsection