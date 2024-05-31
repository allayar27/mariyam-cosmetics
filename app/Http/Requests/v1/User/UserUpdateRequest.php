<?php

namespace App\Http\Requests\v1\User;

use Illuminate\Foundation\Http\FormRequest;

class UserUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'string',
            'branch_id' => 'exists:branches,id',
            'position_id' => 'exists:positions,id',
            'time_in' => ['regex:/^(2[0-3]|[01][0-9]):([0-5][0-9])$/'],
            'time_out' => ['regex:/^(2[0-3]|[01][0-9]):([0-5][0-9])$/'],
            'phone' =>'string',
            'images' => 'array',
            'images.*' => 'file',
        ];
    }
}
