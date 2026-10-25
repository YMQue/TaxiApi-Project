<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePromoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
protected function prepareForValidation(): void
{
    if ($this->has('code')) {
        $this->merge(['code' => strtoupper(trim($this->code))]);
    }
}

public function rules(): array
{
    return [
        'code'                => ['required', 'string', 'max:50', 'unique:promo_codes,code'],
        'type'                => ['required', 'in:percentage,fixed'],
        'value'               => ['required', 'numeric', 'gt:0', Rule::when($this->type === 'percentage', ['max:100'])],
        'min_ride_amount'     => ['nullable', 'numeric', 'min:0'],
        'max_discount'        => ['nullable', 'numeric', 'gt:0'],
        'usage_limit'         => ['nullable', 'integer', 'min:1'],
        'user_limit_per_code' => ['nullable', 'integer', 'min:1'],
        'valid_from'          => ['nullable', 'date'],
        'valid_to'            => ['nullable', 'date', 'after:valid_from'],
        'is_active'           => ['nullable', 'boolean'],
    ];
}
}
