<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExhibitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // 認証済みユーザーのみ許可
        return auth()->check();
    }

    /**
     * 送信前に値を整形
     * - price: 全角数字→半角、前後空白除去
     */
    protected function prepareForValidation(): void
    {
        $price = (string) $this->input('price', '');
        $price = mb_convert_kana($price, 'n', 'UTF-8');
        $this->merge(['price' => trim($price)]);
    }

    public function rules(): array
    {
        $allowed = ['良好', '目立った傷や汚れなし', 'やや傷や汚れあり', '状態が悪い'];

        return [
            'name'         => ['required', 'string', 'max:255'],
            'description'  => ['required', 'string', 'max:255'],
            'image'        => ['required', 'file', 'mimes:jpeg,png'],

            // 複数カテゴリ選択（必須・1つ以上）
            'category_ids'   => ['required', 'array', 'min:1'],
            'category_ids.*' => ['exists:categories,id'],

            'condition'    => ['required', Rule::in($allowed)],
            'price'        => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '商品名を入力してください',
            'name.max'      => '商品名は255文字以内で入力してください',

            'description.required' => '商品説明を入力してください',
            'description.max'      => '商品説明は255文字以内で入力してください',

            'image.required' => '商品画像をアップロードしてください',
            'image.mimes'    => '商品画像は.jpegまたは.png形式でアップロードしてください',

            'category_ids.required' => '商品のカテゴリーを選択してください',
            'category_ids.min'      => 'カテゴリーは1つ以上選択してください',
            'category_ids.*.exists' => '選択されたカテゴリーが無効です',

            'condition.required' => '商品の状態を選択してください',
            'condition.in'       => '商品の状態が不正です',

            'price.required' => '販売価格を入力してください',
            'price.numeric'  => '販売価格は数値で入力してください',
            'price.min'      => '販売価格は0円以上で入力してください',
        ];
    }

    /**
     * 検証通過後に保存用に整形
     * - price: 数字以外を除去（カンマや記号を削除）
     */
    protected function passedValidation(): void
    {
        $digitsOnly = preg_replace('/\D/u', '', (string) $this->price) ?? '';
        $this->merge(['price' => $digitsOnly]);
    }
}
