<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
{
    return true; // protected by admin.role:super
}

public function rules(): array
{
    return [
        'name'          => ['required', 'string', 'max:255'],
        'email'         => ['required', 'email', 'unique:admins,email'],
        'password'      => ['required', 'string', 'min:8', 'confirmed'],
        'permissions'   => ['nullable', 'array'],
        'permissions.*' => ['string', Rule::in(config('permissions'))],
    ];
}
}
