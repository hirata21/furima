@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/items/create.css') }}">
@endsection

@section('content')
<div class="item-create-page">
    <h1>商品の出品</h1>

    <form method="POST" action="{{ route('items.store') }}" enctype="multipart/form-data" novalidate>
        @csrf

        <!-- 商品画像 -->
        <label for="image">商品画像</label>
        <div class="image-field">
            <div id="imageBox" class="image-box" aria-label="画像プレビューのみ">
                <img id="imagePreview" alt="選択した画像のプレビュー">
                <button type="button" id="imagePickBtn" class="btn">画像を選択する</button>
            </div>
            <input type="file" id="image" name="image" accept="image/*" style="display:none">
        </div>
        @error('image') <p class="error">{{ $message }}</p> @enderror

        {{-- ▼ 追加 小見出し：商品の詳細 --}}
        <h2 class="form-section-title">商品の詳細</h2>

        <!-- カテゴリ（複数選択） -->
        <label>カテゴリー</label>
        <div class="category-checkboxes">
            @foreach($categories as $category)
            <input
                type="checkbox"
                id="cat{{ $category->id }}"
                name="category_ids[]"
                value="{{ $category->id }}"
                {{ (is_array(old('category_ids')) && in_array($category->id, old('category_ids'))) ? 'checked' : '' }}>
            <label for="cat{{ $category->id }}">{{ $category->name }}</label>
            @endforeach
        </div>
        @error('category_ids') <p class="error">{{ $message }}</p> @enderror

        <!-- 商品の状態（カスタムセレクト） -->
        <label id="conditionLabel" for="condition">商品の状態</label>
        <div class="custom-select" data-select-id="condition">
            <button type="button" class="cs-trigger" aria-labelledby="conditionLabel" aria-haspopup="listbox" aria-expanded="false">
                <span class="cs-value">選択してください</span>
                <span class="cs-arrow" aria-hidden="true">▾</span>
            </button>
            <ul class="cs-list" role="listbox"></ul>
            <select id="condition" name="condition" required class="cs-native">
                <option value="">選択してください</option>
                <option value="良好" {{ old('condition') === '良好' ? 'selected' : '' }}>良好</option>
                <option value="目立った傷や汚れなし" {{ old('condition') === '目立った傷や汚れなし' ? 'selected' : '' }}>目立った傷や汚れなし</option>
                <option value="やや傷や汚れあり" {{ old('condition') === 'やや傷や汚れあり' ? 'selected' : '' }}>やや傷や汚れあり</option>
                <option value="状態が悪い" {{ old('condition') === '状態が悪い' ? 'selected' : '' }}>状態が悪い</option>
            </select>
        </div>
        @error('condition') <p class="error">{{ $message }}</p> @enderror

        {{-- ▼ 追加 小見出し：商品名と説明 --}}
        <h2 class="form-section-title">商品名と説明</h2>

        <!-- 商品名 -->
        <label for="name">商品名</label>
        <input type="text" id="name" name="name" value="{{ old('name') }}" required>
        @error('name') <p class="error">{{ $message }}</p> @enderror

        <!-- ブランド名 -->
        <label for="brand">ブランド名</label>
        <input type="text" id="brand" name="brand" value="{{ old('brand') }}">
        @error('brand') <p class="error">{{ $message }}</p> @enderror

        <!-- 商品説明 -->
        <label for="description">商品説明</label>
        <textarea id="description" name="description" required>{{ old('description') }}</textarea>
        @error('description') <p class="error">{{ $message }}</p> @enderror

        <!-- 販売価格 -->
        <label for="price">販売価格</label>
        <div class="price-input">
            <span class="yen-mark" aria-hidden="true">¥</span>
            <input
                type="text"
                id="price"
                name="price"
                value="{{ old('price') }}"
                inputmode="numeric"
                autocomplete="off"
                placeholder=" "
                required>
        </div>
        @error('price') <p class="error">{{ $message }}</p> @enderror

        <button type="submit">出品する</button>
    </form>
</div>

@endsection

@push('scripts')
<script>
    (() => {
        // 画像プレビュー
        const input = document.getElementById('image');
        const box = document.getElementById('imageBox');
        const preview = document.getElementById('imagePreview');
        const pickBtn = document.getElementById('imagePickBtn');
        let url = null;

        function clearPreview() {
            if (url) {
                URL.revokeObjectURL(url);
                url = null;
            }
            input.value = '';
            preview.removeAttribute('src');
            box.classList.remove('has-image');
        }

        function setPreview(file) {
            if (!file || !file.type?.startsWith('image/')) {
                clearPreview();
                return;
            }
            if (url) URL.revokeObjectURL(url);
            url = URL.createObjectURL(file);
            preview.src = url;
            box.classList.add('has-image');
        }
        input?.addEventListener('change', () => setPreview(input.files[0]));
        pickBtn?.addEventListener('click', () => input.click());

        ['dragenter', 'dragover'].forEach(ev => box?.addEventListener(ev, e => {
            e.preventDefault();
            e.stopPropagation();
            box.classList.add('dragover');
        }));
        ['dragleave', 'drop'].forEach(ev => box?.addEventListener(ev, e => {
            e.preventDefault();
            e.stopPropagation();
            box.classList.remove('dragover');
        }));
        box?.addEventListener('drop', e => {
            const file = e.dataTransfer.files?.[0];
            if (file) setPreview(file);
        });

        // カスタムセレクト
        document.querySelectorAll('.custom-select').forEach(root => {
            const native = root.querySelector('.cs-native');
            const trigger = root.querySelector('.cs-trigger');
            const valueEl = root.querySelector('.cs-value');
            const list = root.querySelector('.cs-list');

            list.innerHTML = '';
            Array.from(native.options).forEach(opt => {
                const li = document.createElement('li');
                li.className = 'cs-option';
                li.role = 'option';
                li.dataset.value = opt.value;
                li.textContent = opt.text;
                li.setAttribute('aria-selected', opt.selected ? 'true' : 'false');
                li.tabIndex = -1;
                list.appendChild(li);
            });

            syncFromNative();

            const setOpen = (open) => {
                trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
                root.classList.toggle('is-open', open);
                if (open)(list.querySelector('.cs-option[aria-selected="true"]') || list.querySelector('.cs-option'))?.focus();
            };
            trigger.addEventListener('click', () => setOpen(trigger.getAttribute('aria-expanded') !== 'true'));
            document.addEventListener('click', (e) => {
                if (!root.contains(e.target)) setOpen(false);
            });

            list.addEventListener('click', (e) => {
                const li = e.target.closest('.cs-option');
                if (!li) return;
                native.value = li.dataset.value;
                native.dispatchEvent(new Event('change'));
                syncFromNative();
                setOpen(false);
            });

            trigger.addEventListener('keydown', (e) => {
                if (['ArrowDown', 'Enter', ' '].includes(e.key)) {
                    e.preventDefault();
                    setOpen(true);
                }
            });
            list.addEventListener('keydown', (e) => {
                const items = Array.from(list.querySelectorAll('.cs-option'));
                let idx = items.findIndex(el => el === document.activeElement);
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    items[Math.min(idx + 1, items.length - 1)].focus();
                }
                if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    items[Math.max(idx - 1, 0)].focus();
                }
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const li = document.activeElement;
                    if (li?.classList.contains('cs-option')) {
                        native.value = li.dataset.value;
                        native.dispatchEvent(new Event('change'));
                        syncFromNative();
                        setOpen(false);
                        trigger.focus();
                    }
                }
                if (e.key === 'Escape') {
                    e.preventDefault();
                    setOpen(false);
                    trigger.focus();
                }
            });

            function syncFromNative() {
                const selectedOpt = native.options[native.selectedIndex];
                valueEl.textContent = selectedOpt ? selectedOpt.text : '';
                root.classList.toggle('is-empty', !native.value);
                list.querySelectorAll('.cs-option').forEach(li => {
                    li.setAttribute('aria-selected', li.dataset.value === native.value ? 'true' : 'false');
                });
            }
        });
    })();
</script>
@endpush