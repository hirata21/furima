<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\LoginRequest;

class FortifyLoginFormRequest
{
    public function handle(Request $request, Closure $next)
    {
        // Fortify の POST /login だけを対象
        if ($request->isMethod('post') && $request->is('login')) {

            // ★ ログ（動作確認用。問題解消後は消してOK）
            \Log::info('[FR MW] start', ['path' => $request->path(), 'method' => $request->method(), 'accept' => $request->header('Accept')]);

            /** @var \App\Http\Requests\LoginRequest $form */
            $form = app(LoginRequest::class);

            // ★ prepareForValidation 相当（整形）
            $normalized = $request->all();
            if (array_key_exists('email', $normalized)) {
                $normalized['email'] = mb_strtolower(trim((string)$normalized['email']));
            }

            // ★ ここで FormRequest の rules / messages / attributes をそのまま渡す
            $validator = Validator::make(
                $normalized,
                $form->rules(),
                method_exists($form, 'messages')   ? $form->messages()   : [],
                method_exists($form, 'attributes') ? $form->attributes() : []
            );

            // ★ LoginRequest に withValidator(...) があれば反映（例：Auth::validateの後付けエラー）
            if (method_exists($form, 'withValidator')) {
                $form->withValidator($validator);
            }

            if ($validator->fails()) {
                \Log::warning('[FR MW] validate failed', ['errors' => $validator->errors()->toArray()]);
                // JSON 期待時のみ 422、それ以外は 302 で戻す（Blade に $errors が出る）
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors'  => $validator->errors(),
                    ], 422);
                }
                return back()->withErrors($validator)->withInput();
            }

            // ★ 後続（Fortify本体）でも整形後の値を使わせる
            $request->replace($normalized);

            \Log::info('[FR MW] validation passed', ['email' => $normalized['email'] ?? null]);
        }

        return $next($request);
    }
}