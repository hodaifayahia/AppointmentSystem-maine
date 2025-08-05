<?php

namespace App\Http\Enum\Payment;

enum PaymentMethodEnum :string
{

    case PREPAYMENT = 'prepayment';
    case POSTPAYMENT = 'postpayment';
    case VERSEMENT = 'versement';

    // Optional: Add a method to get a display name
    public function label(): string
    {
        return match ($this) {
            self::PREPAYMENT => 'Pre-payment',
            self::POSTPAYMENT => 'Post-payment',
            self::VERSEMENT => 'Versement',
        };
    }

    // Optional: Add a method to get an icon
    public function icon(): string
    {
        return match ($this) {
            self::PREPAYMENT => 'fas fa-wallet',
            self::POSTPAYMENT => 'fas fa-file-invoice-dollar',
            self::VERSEMENT => 'fas fa-university',
        };
    }

    // Optional: Get all values as an array for dropdowns
    public static function toArrayForDropdown(): array
    {
        return array_map(fn($case) => [
            'name' => $case->label(),
            'key' => $case->value,
            'icon' => $case->icon(), // if you need icons in dropdown
        ], self::cases());
    }
}
