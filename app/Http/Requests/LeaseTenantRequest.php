<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeaseTenantRequest extends FormRequest
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
            'lease_id' => 'required|exists:leases,id',
            'tenant_id' => 'required|exists:tenants,id',
            'is_primary' => 'boolean',
        ];

        // For update requests, make fields optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['lease_id'] = 'sometimes|required|exists:leases,id';
            $rules['tenant_id'] = 'sometimes|required|exists:tenants,id';
            $rules['is_primary'] = 'sometimes|boolean';
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
            'lease_id.required' => 'A lease must be selected.',
            'lease_id.exists' => 'The selected lease does not exist.',
            'tenant_id.required' => 'A tenant must be selected.',
            'tenant_id.exists' => 'The selected tenant does not exist.',
            'is_primary.boolean' => 'The primary tenant indicator must be true or false.',
        ];
    }
}