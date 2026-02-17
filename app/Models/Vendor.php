<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'contact_person_name',
        'contact_person_email',
        'contact_person_phone',
        'contact_person_title',
        'possible_products',
        'payment_method',
        'etransfer_email',
        'bank_name',
        'transit_number',
        'institute_number',
        'account_number',
        'void_check_path',
        'notes',
        'private',
        'user_id',
    ];

    protected $casts = [
        'private' => 'boolean',
    ];

    /**
     * Relationship with user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if user can update this vendor
     */
    public function canBeUpdatedBy(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        
        if ($user->isEditor()) {
            return $this->user_id === $user->id;
        }
        
        return false;
    }

    /**
     * Check if user can delete this vendor
     */
    public function canBeDeletedBy(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        
        return false;
    }
}
