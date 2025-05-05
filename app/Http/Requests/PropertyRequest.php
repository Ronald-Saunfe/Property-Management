<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PropertyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Authorization is handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $rules = [
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'zip_code' => 'required|string|max:20',
            'property_type' => 'required|string|in:apartment,house,condo,townhouse,commercial',
            'year_built' => 'nullable|date',
            'description' => 'nullable|string',
            'status' => 'required|string|in:active,inactive,maintenance',
        ];

        // For update requests, make fields optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['user_id'] = 'sometimes|required|exists:users,id';
            $rules['name'] = 'sometimes|required|string|max:255';
            $rules['address'] = 'sometimes|required|string|max:255';
            $rules['city'] = 'sometimes|required|string|max:255';
            $rules['state'] = 'sometimes|required|string|max:255';
            $rules['zip_code'] = 'sometimes|required|string|max:20';
            $rules['property_type'] = 'sometimes|required|string|in:apartment,house,condo,townhouse,commercial';
            $rules['status'] = 'sometimes|required|string|in:active,inactive,maintenance';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'user_id.required' => 'A user must be selected.',
            'user_id.exists' => 'The selected user does not exist.',
            'name.required' => 'The property name is required.',
            'address.required' => 'The property address is required.',
            'city.required' => 'The city is required.',
            'state.required' => 'The state is required.',
            'zip_code.required' => 'The zip code is required.',
            'property_type.required' => 'The property type is required.',
            'property_type.in' => 'The property type must be one of: apartment, house, condo, townhouse, commercial.',
            'year_built.date' => 'The year built must be a valid date.',
            'status.required' => 'The property status is required.',
            'status.in' => 'The property status must be one of: active, inactive, maintenance.',
        ];
    }
}