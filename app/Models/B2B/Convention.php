<?php

namespace App\Models\B2B;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\B2B\ConventionDetail;
use App\Models\CRM\Organisme;



class Convention extends Model
{
    protected $fillable = [
        'organisme_id',
        'name',
        'status',
        'created_at',
        'updated_at'
    ];

    public function conventionDetail(): HasOne
    {
        return $this->hasOne(ConventionDetail::class);
    }
    public function organisme(): BelongsTo
    {
        return $this->belongsTo(Organisme::class, 'organisme_id'); // Assuming Organisme is the model and 'organisme_id' is the foreign key
    }
}
