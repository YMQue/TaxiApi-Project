<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DriverIndexRequest extends FormRequest
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
            'search'       => 'nullable|string|max:100',
            'name'         => 'nullable|string|max:100',
            'email'        => 'nullable|string|max:100',
            'phone_number' => 'nullable|string|max:20',
            'status'       => 'nullable|string|max:20',
            'is_online'    => 'nullable|boolean',
            'car_type_id'  => 'nullable|integer',
            'sort_by'      => 'nullable|in:id,rate,status,is_online,created_at',
            'sort_dir'     => 'nullable|in:asc,desc',
            'per_page'     => 'nullable|integer|min:1|max:100',
        ];
    }
}
