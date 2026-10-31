<?php

namespace App\Http\Requests\Admin;

use App\Models\CarTypePricingRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePricingRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'       => ['sometimes', 'nullable', 'string', 'max:100'],
            'start_time' => ['sometimes', 'required', 'date_format:H:i'],
            'end_time'   => ['sometimes', 'required', 'date_format:H:i'],
            'base_fare'  => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'per_km'     => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'per_minute' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'min_fare'   => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'is_active'  => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }

            /** @var CarTypePricingRule $rule */
            $rule = $this->route('pricingRule');

            // use the new value if sent, otherwise keep the current one
            $start    = $this->input('start_time', substr($rule->start_time, 0, 5));
            $end      = $this->input('end_time', substr($rule->end_time, 0, 5));
            $isActive = $this->has('is_active') ? $this->boolean('is_active') : $rule->is_active;

            if ($start === $end) {
                $v->errors()->add('end_time', 'The end time must be different from the start time.');
                return;
            }

            if ($isActive && CarTypePricingRule::overlaps($rule->car_type_id, $start, $end, $rule->id)) {
                $v->errors()->add(
                    'start_time',
                    'This time range overlaps an active pricing rule for this car type.'
                );
            }
        });
    }
}