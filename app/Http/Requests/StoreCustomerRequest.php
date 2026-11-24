<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
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
            'name'  => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'tel' => ['required', 'regex:/^[0-9+\-\s()]{10,15}$/'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'  => 'お名前',
            'email' => 'メールアドレス',
            'tel' => '電話番号',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => '電話番号の形式が正しくありません（ハイフンなしで入力してください）。',
        ];
    }
}
