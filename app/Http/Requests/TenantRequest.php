<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TenantRequest extends FormRequest
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
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:tenants',
            'phone' => 'required|string|max:20',
            'date_of_birth' => 'nullable|date',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'occupation' => 'nullable|string|max:255',
            'income' => 'nullable|numeric|min:0',
            'credit_score' => 'nullable|integer|min:300|max:850',
            'status' => 'required|string|in:active,inactive,pending,evicted',
        ];

        // For update requests, make fields optional and modify unique rule for email
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $tenantId = $this->route('id');
            $rules['first_name'] = 'sometimes|required|string|max:255';
            $rules['last_name'] = 'sometimes|required|string|max:255';
            $rules['email'] = 'sometimes|required|string|email|max:255|unique:tenants,email,' . $tenantId;
            $rules['phone'] = 'sometimes|required|string|max:20';
            $rules['status'] = 'sometimes|required|string|in:active,inactive,pending,evicted';
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
            'first_name.required' => 'The first name is required.',
            'last_name.required' => 'The last name is required.',
            'email.required' => 'The email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already in use.',
            'phone.required' => 'The phone number is required.',
            'date_of_birth.date' => 'Please provide a valid date of birth.',
            'income.numeric' => 'Income must be a number.',
            'income.min' => 'Income cannot be negative.',
            'credit_score.integer' => 'Credit score must be a whole number.',
            'credit_score.min' => 'Credit score must be at least 300.',
            'credit_score.max' => 'Credit score cannot be greater than 850.',
            'status.required' => 'The tenant status is required.',
            'status.in' => 'The tenant status must be active, inactive, pending, or evicted.',
        ];
    }
}