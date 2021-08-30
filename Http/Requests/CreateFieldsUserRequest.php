<?php

namespace Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateFieldsUserRequest extends FormRequest
{
    public function rules()
    {
        return config('encore.user.config.rules');
    }

    public function authorize()
    {
        return true;
    }

    public function messages()
    {
        return [];
    }
}
