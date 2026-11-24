<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketsRequest extends FormRequest
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
            'tickets' => ['required', 'array', 'min:1'],
            'tickets.*.seat_id' => [
                'required',
                'integer',
                Rule::exists('schedule_seats', 'id')
                    ->where('reservation_id', session('reservation_id')),
            ],
            'tickets.*.ticket_type_id' => [
                'required',
                'integer',
                'exists:ticket_types,id,is_active,1',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'tickets.required' => '座席情報が指定されていません。',
            'tickets.*.seat_id.required' => '座席IDが正しく指定されていません。',
            'tickets.*.seat_id.exists' => '選択された座席が存在しません。',
            'tickets.*.ticket_type_id.required' => 'すべての座席の券種を選択してください。',
            'tickets.*.ticket_type_id.exists' => '無効な券種が選択されています。',
        ];
    }
}
