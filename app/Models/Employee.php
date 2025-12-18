<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'full_legal_name',
        'preferred_name',
        'date_of_birth',
        'address',
        'postal_code',
        'country',
        'phone_number',
        'alternate_number',
        'email',
        'emergency_name',
        'emergency_relationship',
        'emergency_address_line1',
        'emergency_address_line2',
        'emergency_city',
        'emergency_state',
        'emergency_postal_code',
        'emergency_country',
        'emergency_phone',
        'emergency_alternate_number',
        'status_in_canada',
        'other_status',
        'sin_number',
        'position',
        'department',
        'hire_date',
        'hourly_rate',
        'facebook',
        'linkedin',
        'twitter',
        'government_id_file',
        'work_permit_file',
        'resume_file',
        'photo_file',
        'void_cheque_file',
        'user_id',
        'status'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'hourly_rate' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function workHours()
    {
        return $this->hasMany(WorkHour::class);
    }
} 