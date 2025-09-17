@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/purchase/show.css') }}">
@endsection

@section('content')
<main class="purchase-page">
    {{-- ← ページ見出しは削除して非表示にしています --}}
    {{-- <header class="purchase-page__header">
    <h1 class="purchase-page__title">購入手続き</h1>
  </header> --}}

    <form method="POST" action="{{ route('purchase.store', $item->id) }}" class="purchase-container" novalidate>
        @csrf

        <div class="purchase-grid">
            <!-- 左カラム -->
            <section class="left-column">
                {{-- アクセシビリティ用の大見出しは視覚的に非表示のまま維持（任意） --}}
                <h2 class="visually-hidden">商品・支払い・配送先</h2>

                <!-- 商品概要（「商品」見出しは削除） -->
                <section class="product-summary">
                    {{-- <h3 class="section-title">商品</h3> --}}
                    <figure class="product-summary__figure">
                        <img src="{{ asset('storage/' . $item->image_path) }}" alt="{{ $item->name }}" class="product-image">
                        <figcaption class="product-info">
                            <p class="product-name">{{ $item->name }}</p>
                            <p class="product-price">¥{{ number_format($item->price) }}</p>
                        </figcaption>
                    </figure>
                </section>

                <div class="divider" role="separator" aria-hidden="true"></div>

                <!-- 支払い方法 -->
                <section class="payment-method" aria-labelledby="paymentLabel">
                    <h3 id="paymentLabel" class="section-title">支払い方法</h3>

                    @php
                    $pmError = $errors->has('payment_method');
                    $pmErrorId = $pmError ? 'error-payment_method' : null;
                    @endphp

                    <div class="custom-select" data-select-id="payment_method">
                        <button
                            type="button"
                            class="cs-trigger"
                            aria-labelledby="paymentLabel"
                            aria-haspopup="listbox"
                            aria-expanded="false"
                            aria-controls="cs-list-payment"
                            @if($pmError) aria-describedby="{{ $pmErrorId }}" @endif>
                            <span class="cs-value">選択してください</span>
                            <span class="cs-arrow" aria-hidden="true">▾</span>
                        </button>

                        <ul id="cs-list-payment" class="cs-list" role="listbox"></ul>

                        {{-- 送信用/JSなしフォールバック用のネイティブselect --}}
                        <select id="payment_method" name="payment_method" required class="cs-native" aria-hidden="true" tabindex="-1">
                            <option value="">選択してください</option>
                            <option value="クレジットカード" {{ old('payment_method') === 'クレジットカード' ? 'selected' : '' }}>クレジットカード</option>
                            <option value="コンビニ払い" {{ old('payment_method') === 'コンビニ払い'   ? 'selected' : '' }}>コンビニ払い</option>
                        </select>

                        <noscript>
                            <div class="noscript-select">
                                <label for="payment_method_fallback" class="visually-hidden">支払い方法</label>
                                <select id="payment_method_fallback" name="payment_method" required>
                                    <option value="">選択してください</option>
                                    <option value="クレジットカード">クレジットカード</option>
                                    <option value="コンビニ払い">コンビニ払い</option>
                                </select>
                            </div>
                        </noscript>
                    </div>

                    @error('payment_method')
                    <p class="error-text" id="error-payment_method">{{ $message }}</p>
                    @enderror
                </section>

                <div class="divider" role="separator" aria-hidden="true"></div>

                <!-- 配送先 -->
                <section class="shipping-address" aria-labelledby="shippingLabel">
                    <h3 id="shippingLabel" class="section-title">配送先</h3>
                    <p class="shipping-address__text">
                        〒{{ $address->postcode ?? '未登録' }}<br>
                        {{ $address->address ?? '' }} {{ $address->building ?? '' }}
                    </p>
                    @error('address')
                    <p class="error-text">{{ $message }}</p>
                    @enderror
                    <a href="{{ route('purchase.address.edit', $item->id) }}" class="edit-link">変更する</a>
                </section>
            </section>

            <!-- 右カラム -->
            <aside class="right-column">
                <h2 class="visually-hidden">支払い情報</h2>

                <div class="summary-box">
                    <dl class="summary-list">
                        <div class="summary-row">
                            <dt>商品代金</dt>
                            <dd>¥{{ number_format($item->price) }}</dd>
                        </div>
                        <div class="summary-row">
                            <dt>支払い方法</dt>
                            <dd id="selected-payment">{{ old('payment_method', '未選択') }}</dd>
                        </div>
                    </dl>
                </div>

                <button type="submit" class="purchase-button">購入する</button>
            </aside>
        </div>
    </form>
</main>

<script>
    (() => {
        const root = document.querySelector('.custom-select[data-select-id="payment_method"]');
        if (!root) return;

        const native = root.querySelector('.cs-native');
        const trigger = root.querySelector('.cs-trigger');
        const valueEl = root.querySelector('.cs-value');
        const list = root.querySelector('.cs-list');
        const out = document.getElementById('selected-payment');

        root.classList.add('is-enhanced');

        function renderOptions() {
            list.innerHTML = '';
            Array.from(native.options).forEach((opt, i) => {
                const li = document.createElement('li');
                li.className = 'cs-option';
                li.setAttribute('role', 'option');
                li.id = `cs-opt-${i}`; // ← バッククォートで修正済み
                li.dataset.value = opt.value;
                li.textContent = opt.textContent;
                if (opt.selected) li.setAttribute('aria-selected', 'true');
                list.appendChild(li);
            });
            syncLabel();
        }

        function syncLabel() {
            const selOpt = native.selectedOptions[0];
            valueEl.textContent = selOpt ? selOpt.textContent : '選択してください';
            if (out) out.textContent = native.value || '未選択';
        }

        function open() {
            root.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');
        }

        function close() {
            root.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
        }

        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            root.classList.contains('is-open') ? close() : open();
        });

        list.addEventListener('click', (e) => {
            const li = e.target.closest('.cs-option');
            if (!li) return;
            native.value = li.dataset.value;
            list.querySelectorAll('.cs-option[aria-selected="true"]').forEach(x => x.removeAttribute('aria-selected'));
            li.setAttribute('aria-selected', 'true');
            native.dispatchEvent(new Event('change', {
                bubbles: true
            }));
            close();
        });

        root.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') close();
        });
        document.addEventListener('click', (e) => {
            if (!root.contains(e.target)) close();
        });

        native.addEventListener('change', syncLabel);

        renderOptions();
        syncLabel();
    })();
</script>
@endsection