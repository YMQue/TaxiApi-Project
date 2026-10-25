<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePromoRequest extends FormRequest
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
    $promo = $this->route('promo');
    $type  = $this->input('type', $promo->type);

    return [
        'code'                => ['sometimes', 'string', 'max:50', Rule::unique('promo_codes', 'code')->ignore($promo->id)],
        'type'                => ['sometimes', 'in:percentage,fixed'],
        'value'               => ['sometimes', 'numeric', 'gt:0', Rule::when($type === 'percentage', ['max:100'])],
        'min_ride_amount'     => ['sometimes', 'nullable', 'numeric', 'min:0'],
        'max_discount'        => ['sometimes', 'nullable', 'numeric', 'gt:0'],
        'usage_limit'         => ['sometimes', 'nullable', 'integer', 'min:1'],
        'user_limit_per_code' => ['sometimes', 'integer', 'min:1'],
        'valid_from'          => ['sometimes', 'nullable', 'date'],
        'valid_to'            => ['sometimes', 'nullable', 'date'],
        'is_active'           => ['sometimes', 'boolean'],
    ];
}
}
