<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
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
            'commission_rate'            => ['sometimes', 'required', 'numeric', 'between:0,100'],
            'driver_search_radius_km'    => ['sometimes', 'required', 'numeric', 'between:0.5,100'],
            'ride_request_timeout_sec'   => ['sometimes', 'required', 'integer', 'between:5,300'],
            'max_driver_search_attempts' => ['sometimes', 'required', 'integer', 'between:1,20'],
            'min_withdrawal_amount'      => ['sometimes', 'required', 'numeric', 'min:0'],
            'cancellation_fee'           => ['sometimes', 'required', 'numeric', 'min:0'],
        ];
    }
    protected function prepareForValidation(): void
    {
        // يمنع طلبًا فارغًا تمامًا من المرور بصمت
        if (empty($this->all())) {
            $this->merge(['_empty' => null]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->has('_empty')) {
                $validator->errors()->add('settings', 'At least one setting must be provided.');
            }
        });
    }
}
