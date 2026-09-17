<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
        'name'          => 'required|string|max:255',
        'email'         => 'nullable|email|unique:users,email',
        'avatar'        => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        'is_driver'     => 'boolean',
        // Driver fields if is_driver == true
        'car_type_id'   => 'required_if:is_driver,true|exists:car_types,id',
        'license_number'=> 'required_if:is_driver,true|string|unique:drivers',
        'plate_number'  => 'required_if:is_driver,true|string|unique:drivers',
        ];
    }
    protected function prepareForValidation()
{
    $this->merge([
        'is_driver' => filter_var($this->is_driver, FILTER_VALIDATE_BOOLEAN),
    ]);
}

}
