<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeaseRequest extends FormRequest
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
            'unit_id' => 'required|exists:units,id',
            'tenant_id' => 'required|exists:tenants,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'monthly_rent' => 'required|numeric|min:0',
            'security_deposit' => 'required|numeric|min:0',
            'lease_type' => 'required|string|in:fixed,month-to-month',
            'payment_day' => 'required|integer|min:1|max:31',
            'status' => 'required|string|in:active,pending,expired,terminated',
            'document_path' => 'nullable|string',
            'notes' => 'nullable|string',
            'tenants' => 'nullable|array',
            'tenants.*.tenant_id' => 'required|exists:tenants,id',
            'tenants.*.is_primary' => 'required|boolean',
        ];

        // For update requests, make fields optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            foreach ($rules as $field => $rule) {
                if (strpos($field, 'tenants.') === false) { // Don't modify nested validation rules
                    $rules[$field] = 'sometimes|' . $rule;
                }
            }
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
            'unit_id.required' => 'A unit must be selected.',
            'unit_id.exists' => 'The selected unit does not exist.',
            'tenant_id.required' => 'A primary tenant must be selected.',
            'tenant_id.exists' => 'The selected tenant does not exist.',
            'start_date.required' => 'The lease start date is required.',
            'start_date.date' => 'The lease start date must be a valid date.',
            'end_date.required' => 'The lease end date is required.',
            'end_date.date' => 'The lease end date must be a valid date.',
            'end_date.after' => 'The lease end date must be after the start date.',
            'monthly_rent.required' => 'The monthly rent amount is required.',
            'monthly_rent.numeric' => 'The monthly rent must be a number.',
            'monthly_rent.min' => 'The monthly rent cannot be negative.',
            'security_deposit.required' => 'The security deposit amount is required.',
            'security_deposit.numeric' => 'The security deposit must be a number.',
            'security_deposit.min' => 'The security deposit cannot be negative.',
            'lease_type.required' => 'The lease type is required.',
            'lease_type.in' => 'The lease type must be either fixed or month-to-month.',
            'payment_day.required' => 'The payment day is required.',
            'payment_day.integer' => 'The payment day must be a number.',
            'payment_day.min' => 'The payment day must be at least 1.',
            'payment_day.max' => 'The payment day cannot be greater than 31.',
            'status.required' => 'The lease status is required.',
            'status.in' => 'The lease status must be active, pending, expired, or terminated.',
            'tenants.*.tenant_id.required' => 'Each tenant must have a valid ID.',
            'tenants.*.tenant_id.exists' => 'One or more selected tenants do not exist.',
            'tenants.*.is_primary.required' => 'Each tenant must specify if they are primary or not.',
            'tenants.*.is_primary.boolean' => 'The primary tenant indicator must be true or false.',
        ];
    }
}