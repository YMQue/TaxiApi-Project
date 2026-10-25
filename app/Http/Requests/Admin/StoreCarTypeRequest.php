<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCarTypeRequest extends FormRequest
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
            'type'       => ['required', Rule::in(['Economy', 'Comfort', 'XL'])],
            'icon'       => ['nullable', 'string', 'max:255'],
            'base_fare'  => ['nullable', 'numeric', 'min:0'],
            'per_km'     => ['nullable', 'numeric', 'min:0'],
            'per_minute' => ['nullable', 'numeric', 'min:0'],
            'min_fare'   => ['nullable', 'numeric', 'min:0'],
            'capacity'   => ['nullable', 'integer', 'min:1'],
        ];
    }
}
