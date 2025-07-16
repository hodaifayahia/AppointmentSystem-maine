<?php

namespace App\Models\B2B;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\B2B\Convention;

class ConventionDetail extends Model
{
   protected $table = 'conventions_details';

    protected $fillable = [
        'convention_id',
        'start_date',
        'end_date',
        'family_auth',
        'max_price',
        'min_price',
        'discount_percentage'
    ];

    public function convention(): BelongsTo
    {
        return $this->belongsTo(Convention::class);
    }
}
