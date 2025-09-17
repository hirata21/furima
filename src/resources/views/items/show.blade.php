@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/items/show.css') }}">
@endsection

@section('content')
<div class="item-detail-page">
    <div class="item-detail-container">

        {{-- 商品画像 --}}
        <div class="item-image">
            @php
            // プレースホルダ画像が無ければ storage 側のみでも構いません
            $imgSrc = $item->image_path
            ? asset('storage/' . $item->image_path)
            : asset('images/placeholder.png');
            @endphp
            <img
                src="{{ $imgSrc }}"
                alt="{{ $item->name }}の画像"
                loading="lazy"
                decoding="async">
        </div>

        {{-- 商品情報 --}}
        <div class="item-info">
            <h1 class="item-title">{{ $item->name }}</h1>

            @if(!empty($item->brand))
            <p class="brand">{{ $item->brand }}</p>
            @endif

            <p class="price">
                ¥{{ number_format($item->price) }}
                <span class="tax-note">(税込)</span>
            </p>

            {{-- いいね／コメント数 --}}
            <div class="icon-row" aria-label="ステータス">
                <div class="icon-block">
                    @php
                    // コントローラ側で $liked を用意して渡す実装推奨
                    // $liked = auth()->check() ? $item->likes()->where('user_id', auth()->id())->exists() : false;
                    @endphp

                    @auth
                    <form method="POST" action="{{ route('like.toggle', $item->id) }}">
                        @csrf
                        <button
                            type="submit"
                            class="icon-button {{ (!empty($liked) && $liked) ? 'is-liked' : '' }}"
                            aria-pressed="{{ (!empty($liked) && $liked) ? 'true' : 'false' }}"
                            aria-label="お気に入り{{ (!empty($liked) && $liked) ? '解除' : '登録' }}"
                            title="お気に入り{{ (!empty($liked) && $liked) ? '解除' : '登録' }}">
                            {{ (!empty($liked) && $liked) ? '★' : '☆' }}
                        </button>
                    </form>
                    @else
                    <a class="icon-button" href="{{ route('auth.login.form') }}" title="ログインしてお気に入りに追加">☆</a>
                    @endauth

                    <span class="icon-count" aria-label="お気に入り数">{{ $item->likes->count() }}</span>
                </div>

                <div class="icon-block">
                    <div class="comment-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                            width="28" height="28" fill="none" stroke="#555"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 3a9 9 0 0 1 0 18c-1.38 0-2.69-.28-3.88-.78L4 21l1.18-3.53A8.97 8.97 0 0 1 3 12a9 9 0 0 1 9-9z" />
                        </svg>
                    </div>
                    <span class="icon-count" aria-label="コメント数">{{ $item->comments->count() }}</span>
                </div>
            </div>

            {{-- 購入ボタン or SOLD --}}
            @if(!$item->is_sold)
            <a href="{{ route('purchase.show', $item->id) }}" class="purchase-button">購入手続きへ</a>
            @else
            <span class="sold-label" aria-label="売り切れ">SOLD</span>
            @endif

            {{-- 商品説明 --}}
            <h2 class="section-title">商品説明</h2>
            <p class="description">{{ $item->description }}</p>

            {{-- 商品の情報（セマンティックな dl 構造） --}}
            <h2 class="section-title">商品の情報</h2>
            <dl class="item-spec">

                <div class="spec-row">
                    <dt class="spec-label">カテゴリ</dt>
                    <dd class="spec-value">
                        <ul class="category-chips" aria-label="カテゴリ一覧">
                            @forelse ($item->categories as $category)
                            <li>
                                <span class="chip" title="{{ $category->name }}">
                                    <span class="chip-label">{{ $category->name }}</span>
                                </span>
                            </li>
                            @empty
                            <li><span class="muted">未設定</span></li>
                            @endforelse
                        </ul>
                    </dd>
                </div>

                <div class="spec-row">
                    <dt class="spec-label">商品の状態</dt>
                    <dd class="spec-value">{{ $item->condition }}</dd>
                </div>

            </dl>

            {{-- コメント一覧 --}}
            <h2 class="section-title">コメント（{{ $item->comments->count() }}）</h2>
            @forelse ($item->comments as $comment)
            <article class="comment-item">
                <header class="comment-header">
                    <img
                        src="{{ $comment->user->profile_image ? asset('storage/'.$comment->user->profile_image) : asset('images/default-user.png') }}"
                        alt=""
                        class="comment-profile-img"
                        loading="lazy"
                        decoding="async">
                    <span class="comment-username">{{ $comment->user->name }}</span>
                </header>
                <div class="comment-body">
                    <p class="comment-text">{{ $comment->content }}</p>
                </div>
            </article>
            @empty
            <p class="muted">まだコメントはありません。</p>
            @endforelse

            {{-- コメント投稿フォーム（name はバックエンドに合わせて） --}}
            <h3 class="section-subtitle">商品のコメント</h3>
            <form method="POST" action="{{ route('comments.store', $item->id) }}">
                @csrf
                <textarea
                    name="comment"
                    class="comment-textarea"
                    rows="6">{{ old('comment') }}</textarea>
                @error('comment')
                <div class="error">{{ $message }}</div>
                @enderror

                <button type="submit" class="submit-comment">コメントを送信する</button>
            </form>
        </div>

    </div>
</div>
@endsection