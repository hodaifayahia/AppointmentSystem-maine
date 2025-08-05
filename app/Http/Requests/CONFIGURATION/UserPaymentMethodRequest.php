<?php

namespace App\Http\Requests\CONFIGURATION;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\Payment\PaymentMethodEnum; // Don't forget to import your Enum

class UserPaymentMethodRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // For now, we'll return true to allow all requests.
        // In a real application, you would implement authorization logic here,
        // e.g., checking if the authenticated user has the necessary permissions.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
     public function rules(): array
    {
        return [
            // Validate the paymentMethodKey
      'paymentMethodKeys' => 'required|array|min:1', // NOW AN ARRAY
        'paymentMethodKeys.*' => 'required|string', // Ensure each key exists and is a string
            // Validate userIds as an array of integers, where each user ID must exist in the 'users' table
            'userIds' => [
                'required',
                'array',
                'min:1', // Ensure at least one user ID is provided
            ],
            'userIds.*' => [ // Rule for each item in the userIds array
                'required', // Each ID must be present
                'integer',  // Each ID must be an integer
                'exists:users,id', // Each ID must exist in the 'users' table
            ],
            // Validate the status (assuming it's 'active' for bulk assignment as per frontend)
            'status' => [
                'required',
                'string',
                // You can add a specific rule if 'status' has limited accepted values (e.g., 'active', 'inactive')
                Rule::in(['active', 'inactive']), // Example statuses
            ],
        ];
    }
}
