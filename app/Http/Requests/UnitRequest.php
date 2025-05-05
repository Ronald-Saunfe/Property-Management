<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UnitRequest extends FormRequest
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
            'property_id' => 'required|exists:properties,id',
            'unit_number' => 'required|string|max:50',
            'floor_plan' => 'nullable|string|max:255',
            'square_feet' => 'nullable|numeric|min:0',
            'bedrooms' => 'nullable|integer|min:0',
            'bathrooms' => 'nullable|numeric|min:0',
            'monthly_rent' => 'required|numeric|min:0',
            'status' => 'required|string|in:available,occupied,maintenance,reserved',
            'features' => 'nullable|string',
        ];

        // For update requests, make fields optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['property_id'] = 'sometimes|required|exists:properties,id';
            $rules['unit_number'] = 'sometimes|required|string|max:50';
            $rules['monthly_rent'] = 'sometimes|required|numeric|min:0';
            $rules['status'] = 'sometimes|required|string|in:available,occupied,maintenance,reserved';
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
            'property_id.required' => 'A property must be selected.',
            'property_id.exists' => 'The selected property does not exist.',
            'unit_number.required' => 'The unit number is required.',
            'square_feet.numeric' => 'The square footage must be a number.',
            'square_feet.min' => 'The square footage cannot be negative.',
            'bedrooms.integer' => 'The number of bedrooms must be a whole number.',
            'bedrooms.min' => 'The number of bedrooms cannot be negative.',
            'bathrooms.numeric' => 'The number of bathrooms must be a number.',
            'bathrooms.min' => 'The number of bathrooms cannot be negative.',
            'monthly_rent.required' => 'The monthly rent is required.',
            'monthly_rent.numeric' => 'The monthly rent must be a number.',
            'monthly_rent.min' => 'The monthly rent cannot be negative.',
            'status.required' => 'The unit status is required.',
            'status.in' => 'The unit status must be available, occupied, maintenance, or reserved.',
        ];
    }
}