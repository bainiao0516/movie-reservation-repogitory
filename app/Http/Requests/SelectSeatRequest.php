<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SelectSeatRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'schedule_id' => ['required', 'integer', 'exists:schedules,id'],
            'seat_ids' => ['required', 'array', 'min:1', 'max:6'],
            'seat_ids.*' => ['required', 'integer', 'exists:schedule_seats,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'schedule_id' => 'スケジュール',
            'seat_ids' => '座席',
            'seat_ids.*' => '座席',
        ];
    }

    public function messages(): array
    {
        return [
            'schedule_id.required' => '上映スケジュールが正しく指定されていません。',
            'seat_ids.required' => '座席を1つ以上選択してください。',
            'seat_ids.max' => '一度に予約できるのは最大6席までです。',
            'seat_ids.*.exists' => '選択された座席は指定されたスケジュールに存在しません。',
        ];
    }
}
