<?php

namespace App\Http\Resources\CONFIGURATION;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserPaymentMethodResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $paymentMethodsString = '';

        // If 'allowed_payment_methods' exists and is an array (due to model casting)
        if ($this->payment_method_key && is_array($this->payment_method_key)) {
            // Join the array elements with a comma and a space
            $paymentMethodsString = implode(', ', $this->payment_method_key);
        }
        // If your column was a comma-separated string directly in the DB
        // and not a JSON column cast to array:
        // elseif ($this->payment_methods) {
        //     $paymentMethodsString = $this->payment_methods;
        // }


        return [
            'id' => $this->id,
            'name' => $this->user->name ?? $this->name,
            'email' => $this->user->email ?? $this->email,
            'status' => $this->status,
            'allowedMethods' => $paymentMethodsString, // This will be "postpayment, versement"
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}