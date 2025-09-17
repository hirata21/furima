<?php

namespace App\Actions\Fortify;

use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ValidateLoginWithFormRequest
{
    public function __invoke(Request $request): void
    {
        /** @var \App\Http\Requests\LoginRequest $form */
        $form = app(LoginRequest::class);

        Validator::make(
            $request->all(),
            $form->rules(),
            method_exists($form, 'messages')   ? $form->messages()   : [],
            method_exists($form, 'attributes') ? $form->attributes() : []
        )->validate();
    }
}
