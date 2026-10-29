<?php

namespace App\Http\Requests;

use App\Models\CarTypePricingRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StorePricingRuleRequest extends FormRequest
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
            'car_type_id' => ['required', 'integer', 'exists:car_types,id'],
            'name'        => ['nullable', 'string', 'max:100'],
            'start_time'  => ['required', 'date_format:H:i'],
            'end_time'    => ['required', 'date_format:H:i', 'different:start_time'],
            'base_fare'   => ['nullable', 'numeric', 'min:0'],
            'per_km'      => ['nullable', 'numeric', 'min:0'],
            'per_minute'  => ['nullable', 'numeric', 'min:0'],
            'min_fare'    => ['nullable', 'numeric', 'min:0'],
            'is_active'   => ['nullable', 'boolean'],
        ];
    }

     public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            // skip if basic rules already failed, or if the rule is created as inactive
            if ($v->errors()->isNotEmpty() || ! $this->boolean('is_active', true)) {
                return;
            }

            if (CarTypePricingRule::overlaps(
                (int) $this->input('car_type_id'),
                $this->input('start_time'),
                $this->input('end_time'),
            )) {
                $v->errors()->add(
                    'start_time',
                    'This time range overlaps an active pricing rule for this car type.'
                );
            }
        });
    }
}
