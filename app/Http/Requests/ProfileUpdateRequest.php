<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
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
        'name'  => 'sometimes|string|max:255',
        'email' => 'sometimes|email|unique:users,email,' . $this->user()->id,
        'avatar'=> 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ];
    }
}
