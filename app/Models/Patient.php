<?php

namespace App\Models;

use App\Models\Appointment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use App\Models\Allergies;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Patient extends Model
{
    use HasFactory, Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'Firstname',
        'phone',
        'Lastname',
        'Parent',
        'Idnum',
         'age',
        'weight',
        'created_by',
        'dateOfBirth',
    ];
    protected $casts = [
        'age' => 'integer',
        'weight' => 'decimal:2',
    ];

    public function appointments()
{
    return $this->hasMany(Appointment::class);
}

    public function Allergies()
    {
        return $this->hasMany(Allergies::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function scopeSearch($query, $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('Firstname', 'like', "%{$searchTerm}%")
                ->orWhere('Lastname', 'like', "%{$searchTerm}%")
                ->orWhere('Idnum', 'like', "%{$searchTerm}%");
        });
    }
    public function scopeFilterByDate($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
