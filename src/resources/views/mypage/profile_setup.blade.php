@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/mypage/profile_setup.css') }}">
@endsection

@section('content')
<main class="profile-setup">
    <div class="profile-setup__container">
        <header class="profile-setup__header">
            <h1 class="profile-setup__title">プロフィール設定</h1>
        </header>

        <form method="POST" action="{{ route('profile.store') }}" enctype="multipart/form-data" novalidate>
            @csrf

            <fieldset class="form-section">
                <legend class="visually-hidden">基本情報</legend>

                {{-- プロフィール画像 --}}
                <div class="form-row form-row--image">
                    <div class="profile-image">
                        <img
                            id="profilePreview"
                            src="{{ $user->profile_image ? asset('storage/' . $user->profile_image) : '' }}"
                            class="profile-image__preview {{ $user->profile_image ? '' : 'profile-image__preview--default' }}"
                            alt="">
                    </div>

                    <div class="profile-image__controls">
                        <label for="profileImageInput" class="button button--secondary">
                            画像を選択する
                        </label>
                        <input
                            id="profileImageInput"
                            type="file"
                            name="profile_image"
                            accept="image/jpeg,image/png"
                            class="visually-hidden">

                        @error('profile_image')
                        <p class="error" id="error-profile_image">{{ $message }}</p>
                        @enderror

                        {{-- 既存画像パスの保持（アプリ仕様で使用しているなら残す） --}}
                        <input type="hidden" name="selected_image" id="selectedImagePath" value="{{ $user->profile_image }}">
                    </div>
                </div>

                {{-- ユーザー名（必須・20文字以内） --}}
                <div class="form-row">
                    <label for="name">ユーザー名</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name', $user->name) }}"
                        maxlength="20"
                        required
                        aria-invalid="@error('name') true @else false @enderror"
                        @error('name') aria-describedby="error-name" @enderror
                        autocomplete="name">
                    @error('name')
                    <p class="error" id="error-name">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 郵便番号（必須・ハイフンあり8文字）例: 123-4567 --}}
                <div class="form-row">
                    <label for="postcode">郵便番号</label>
                    <input
                        type="text"
                        id="postcode"
                        name="postcode"
                        value="{{ old('postcode', optional($user->address)->postcode) }}"
                        placeholder="123-4567"
                        inputmode="numeric"
                        pattern="^\d{3}-\d{4}$"
                        maxlength="8"
                        required
                        aria-invalid="@error('postcode') true @else false @enderror"
                        @error('postcode') aria-describedby="error-postcode" @enderror
                        autocomplete="postal-code">
                    @error('postcode')
                    <p class="error" id="error-postcode">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 住所（必須） --}}
                <div class="form-row">
                    <label for="address">住所</label>
                    <input
                        type="text"
                        id="address"
                        name="address"
                        value="{{ old('address', optional($user->address)->address) }}"
                        required
                        aria-invalid="@error('address') true @else false @enderror"
                        @error('address') aria-describedby="error-address" @enderror
                        autocomplete="street-address">
                    @error('address')
                    <p class="error" id="error-address">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 建物名（任意） --}}
                <div class="form-row">
                    <label for="building">建物名</label>
                    <input
                        type="text"
                        id="building"
                        name="building"
                        value="{{ old('building', optional($user->address)->building) }}"
                        autocomplete="address-line2">
                    @error('building')
                    <p class="error" id="error-building">{{ $message }}</p>
                    @enderror
                </div>

            </fieldset>

            <div class="form-actions">
                <button type="submit" class="button button--primary">更新する</button>
            </div>
        </form>
    </div>
</main>

<script>
    // 選択画像の即時プレビュー（切替時にURLを解放）
    (function() {
        const input = document.getElementById('profileImageInput');
        const img = document.getElementById('profilePreview');
        let currentURL = null;

        if (!input || !img) return;

        input.addEventListener('change', (e) => {
            const file = e.target.files && e.target.files[0];
            if (!file) return;

            if (currentURL) {
                URL.revokeObjectURL(currentURL);
            }
            currentURL = URL.createObjectURL(file);
            img.src = currentURL;
        });
    })();
</script>
@endsection