<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'profile_image' => ['nullable', 'file', 'mimes:jpeg,png'],
            'name'          => ['required', 'string', 'max:20'],
            'postcode'      => ['required', 'regex:/^\d{3}-\d{4}$/'],
            'address'       => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'profile_image.mimes' => 'プロフィール画像は.jpegまたは.png形式でアップロードしてください',
            'name.required'       => 'ユーザー名を入力してください',
            'name.max'            => 'ユーザー名は20文字以内で入力してください',
            'postcode.required'   => '郵便番号を入力してください',
            'postcode.regex'      => '郵便番号はハイフンありの8文字で入力してください',
            'address.required'    => '住所を入力してください',
        ];
    }
}