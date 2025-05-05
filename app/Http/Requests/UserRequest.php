<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:admin,property_manager,tenant,owner',
            'phone' => 'nullable|string|max:20',
        ];

        // For update requests, make fields optional and modify unique rule for email
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $userId = $this->route('id');
            $rules['name'] = 'sometimes|required|string|max:255';
            $rules['email'] = 'sometimes|required|string|email|max:255|unique:users,email,' . $userId;
            $rules['password'] = 'nullable|string|min:8';
            $rules['role'] = 'sometimes|required|string|in:admin,property_manager,tenant,owner';
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
            'name.required' => 'The name is required.',
            'email.required' => 'The email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already in use.',
            'password.required' => 'The password is required.',
            'password.min' => 'The password must be at least 8 characters.',
            'role.required' => 'The user role is required.',
            'role.in' => 'The user role must be admin, property_manager, tenant, or owner.',
        ];
    }
}