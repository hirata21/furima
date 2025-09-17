@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/purchase/address_edit.css') }}">
@endsection

@section('content')
<main class="address-edit">
    <div class="address-edit__container">
        <header class="address-edit__header">
            <h1 class="address-edit__title">住所の変更</h1>
        </header>

        {{-- エラーサマリ（複数項目のとき頭で一覧表示） --}}
        @if ($errors->any())
        <div class="form-errors" role="alert" aria-live="polite">
            <ul>
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('purchase.address.update', $item->id) }}" novalidate>
            @csrf
            {{-- もしルートが PUT/PATCH を想定しているなら以下を有効化 --}}
            {{-- @method('PUT') --}}

            {{-- 郵便番号（必須・ハイフンあり8桁） --}}
            <div class="form-row">
                <label for="postcode">郵便番号</label>
                <input
                    type="text"
                    name="postcode"
                    id="postcode"
                    value="{{ old('postcode', $address->postcode ?? '') }}"
                    inputmode="numeric"
                    pattern="^\d{3}-\d{4}$"
                    maxlength="8"
                    required
                    aria-invalid="@error('postcode') true @else false @enderror"
                    @error('postcode') aria-describedby="error-postcode" @enderror
                    autocomplete="postal-code"
                    autofocus>
                @error('postcode') <p class="error" id="error-postcode">{{ $message }}</p> @enderror
            </div>

            {{-- 住所（必須） --}}
            <div class="form-row">
                <label for="address">住所</label>
                <input
                    type="text"
                    name="address"
                    id="address"
                    value="{{ old('address', $address->address ?? '') }}"
                    required
                    aria-invalid="@error('address') true @else false @enderror"
                    @error('address') aria-describedby="error-address" @enderror
                    autocomplete="street-address">
                @error('address') <p class="error" id="error-address">{{ $message }}</p> @enderror
            </div>

            {{-- 建物名（任意） --}}
            <div class="form-row">
                <label for="building">建物名</label>
                <input
                    type="text"
                    name="building"
                    id="building"
                    value="{{ old('building', $address->building ?? '') }}"
                    autocomplete="address-line2">
                @error('building') <p class="error" id="error-building">{{ $message }}</p> @enderror
            </div>

            <div class="form-actions">
                <button type="submit" class="update-button">更新する</button>
            </div>
        </form>
    </div>
</main>
@endsection