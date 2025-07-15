<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organisme extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'organismes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'legal_form',
        'trade_register_number',
        'tax_id_nif',
        'statistical_id',
        'article_number',
        'wilaya',
        'address',
        'postal_code',
        'phone',
        'fax',
        'mobile',
        'email',
        'website',
        'latitude',
        'longitude',
        'initial_invoice_number',
        'initial_credit_note_number',
        'logo_url',
        'profile_image_url',
        'description',
        'industry',
        'creation_date',
        'number_of_employees',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'creation_date' => 'date',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'number_of_employees' => 'integer',
    ];

    /**
     * The attributes that should be unique.
     * These are not explicitly handled by Eloquent's mass assignment
     * but are good to note for validation.
     *
     * @var array<int, string>
     */
    protected $unique = [
        'trade_register_number',
        'tax_id_nif',
        'statistical_id',
        'email',
    ];
}