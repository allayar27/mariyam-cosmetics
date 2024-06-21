<?php

namespace App\Http\Requests\v2;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleAddRequest extends FormRequest
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
            'name' => 'required|string|max:255|unique:schedules,name',
            'days' => 'required|array|size:7',
            'days.*.day_of_week' => 'required|string',
            'days.*.time_in' => 'nullable',
            'days.*.time_out' => 'nullable',
            'days.*.is_work_day' => 'required|boolean',
        ];
    }
}
