<?php

namespace Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleApiRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required',
            //'slug' => "required|unique:roles,slug,{$this->id}",
        ];
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
