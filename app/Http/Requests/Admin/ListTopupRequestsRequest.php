<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListTopupRequestsRequest extends FormRequest
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
            'status'     => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
            'method'     => ['nullable', 'string', 'max:50'],
            'user_id'    => ['nullable', 'integer', 'exists:users,id'],
            'search'     => ['nullable', 'string', 'max:100'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'gte:min_amount'],
            'date_from'  => ['nullable', 'date'],
            'date_to'    => ['nullable', 'date', 'after_or_equal:date_from'],
            'sort_by'    => ['nullable', Rule::in(['id', 'created_at', 'amount', 'status'])],
            'sort_dir'   => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page'   => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
