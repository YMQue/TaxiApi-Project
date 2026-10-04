<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListWithdrawRequestsRequest extends FormRequest
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
            'driver_id'  => ['nullable', 'integer', 'exists:drivers,id'],
            'bank_name'  => ['nullable', 'string', 'max:100'],
            'search'     => ['nullable', 'string', 'max:100'], // bank_name, account_number, iban
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'gte:min_amount'],
            'date_from'  => ['nullable', 'date'],
            'date_to'    => ['nullable', 'date', 'after_or_equal:date_from'],
            'sort_by'    => ['nullable', Rule::in(['id', 'created_at', 'processed_at', 'amount', 'status'])],
            'sort_dir'   => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page'   => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}