<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
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
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'payment_date' => 'nullable|date',
            'payment_method' => 'nullable|string|max:255',
            'transaction_id' => 'nullable|string|max:255',
            'status' => 'required|string|in:pending,paid,late,partial',
            'notes' => 'nullable|string',
        ];

        // For update requests, make fields optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['lease_id'] = 'sometimes|required|exists:leases,id';
            $rules['amount'] = 'sometimes|required|numeric|min:0';
            $rules['due_date'] = 'sometimes|required|date';
            $rules['status'] = 'sometimes|required|string|in:pending,paid,late,partial';
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
            'amount.required' => 'The payment amount is required.',
            'amount.numeric' => 'The payment amount must be a number.',
            'amount.min' => 'The payment amount must be at least 0.',
            'due_date.required' => 'The due date is required.',
            'due_date.date' => 'The due date must be a valid date.',
            'payment_date.date' => 'The payment date must be a valid date.',
            'status.required' => 'The payment status is required.',
            'status.in' => 'The payment status must be one of: pending, paid, late, partial.',
        ];
    }
}