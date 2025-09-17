<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class PurchaseRequest extends FormRequest
{
    /**
     * このリクエストを実行できるか
     */
    public function authorize(): bool
    {
        // ログインしているユーザーのみ許可
        return Auth::check();
    }

    /**
     * バリデーション前に値を整形
     */
    protected function prepareForValidation(): void
    {
        // 支払い方法の前後スペースを除去
        $pm = trim((string) $this->input('payment_method', ''));
        $this->merge(['payment_method' => $pm]);
    }

    /**
     * バリデーションルール
     */
    public function rules(): array
    {
        return [
            'payment_method' => ['required'],
        ];
    }

    /**
     * エラーメッセージ
     */
    public function messages(): array
    {
        return [
            'payment_method.required' => '支払い方法を選択してください',
        ];
    }

    /**
     * 追加バリデーション
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            // ユーザーに住所が登録されているかを検証
            $addr = Auth::user()?->address; // hasOne想定
            if (!$addr || empty($addr->postcode) || empty($addr->address)) {
                // Bladeで @error('address') で拾えるように 'address' キーを使用
                $v->errors()->add('address', '配送先住所を登録してください。');
            }
        });
    }
}