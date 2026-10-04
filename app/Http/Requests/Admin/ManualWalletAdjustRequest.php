<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ManualWalletAdjustRequest extends FormRequest
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
            'user_id'   => ['required', 'integer', 'exists:users,id'],
            'user_type' => ['required', Rule::in(['rider', 'driver'])],
            'type'      => ['required', Rule::in(['credit', 'debit'])],
            'amount'    => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'reason'    => ['required', 'string', 'min:3', 'max:255'],
        ];
    }
}
