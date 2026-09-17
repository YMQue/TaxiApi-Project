<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestRideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'car_type_id'    => 'required|exists:car_types,id',
            'pickup_lat'     => 'required|numeric|between:-90,90',
            'pickup_lng'     => 'required|numeric|between:-180,180',
            'pickup_address' => 'nullable|string|max:255',
            'drop_lat'       => 'required|numeric|between:-90,90',
            'drop_lng'       => 'required|numeric|between:-180,180',
            'drop_address'   => 'nullable|string|max:255',
            'payment_method' => 'nullable|string',
        ];
    }
}