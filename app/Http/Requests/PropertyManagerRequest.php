<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PropertyManagerRequest extends FormRequest
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
            'user_id' => 'required|exists:users,id',
            'is_primary' => 'required|boolean',
        ];

        // For update requests, make fields optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['property_id'] = 'sometimes|required|exists:properties,id';
            $rules['user_id'] = 'sometimes|required|exists:users,id';
            $rules['is_primary'] = 'sometimes|required|boolean';
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
            'user_id.required' => 'A user must be selected.',
            'user_id.exists' => 'The selected user does not exist.',
            'is_primary.required' => 'The primary manager status is required.',
            'is_primary.boolean' => 'The primary manager status must be true or false.',
        ];
    }
}