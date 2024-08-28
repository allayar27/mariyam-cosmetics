<?php

namespace App\Http\Requests\v1\Client;

use Illuminate\Foundation\Http\FormRequest;

class ClientAttendanceAddRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_id' =>'required',
            'gender' => 'required|string',
            'age' => 'required|integer',
            'device_id' => 'required|exists:devices,id',
            'date' => 'required',
            'score' => 'required|numeric'
        ];
    }
}
