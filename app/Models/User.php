<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is editor
     */
    public function isEditor(): bool
    {
        return $this->role === 'editor';
    }

    /**
     * Check if user is viewer
     */
    public function isViewer(): bool
    {
        return $this->role === 'viewer';
    }

    /**
     * Check if user is staff
     */
    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    /**
     * Check if user can create entries
     */
    public function canCreate(): bool
    {
        return $this->isAdmin() || $this->isEditor();
    }

    /**
     * Check if user can update entries
     */
    public function canUpdate(): bool
    {
        return $this->isAdmin() || $this->isEditor();
    }

    /**
     * Check if user can delete entries
     */
    public function canDelete(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Check if user can manage users
     */
    public function canManageUsers(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Check if user can update a specific daily sale
     */
    public function canUpdateDailySale(DailySale $dailySale): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        
        if ($this->isEditor()) {
            return $dailySale->user_id === $this->id;
        }
        
        return false;
    }

    /**
     * Check if user can update a specific daily fuel
     */
    public function canUpdateDailyFuel(DailyFuel $dailyFuel): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        
        if ($this->isEditor()) {
            return $dailyFuel->user_id === $this->id;
        }
        
        return false;
    }

    /**
     * Check if user can view transactions
     */
    public function canViewTransactions($query = null): bool
    {
        // Staff users cannot view transactions
        if ($this->isStaff()) {
            return false;
        }
        
        // Admins can view all transactions
        if ($this->isAdmin()) {
            return true;
        }
        
        // Editors and viewers can view transactions they created or have access to
        if ($this->isEditor() || $this->isViewer()) {
            if ($query) {
                // Apply filter to show only user's transactions when query is provided
                $query->where('user_id', $this->id);
            }
            return true;
        }
        
        return false;
    }

    /**
     * Check if user can access a specific bank account
     */
    public function canAccessBankAccount($bankAccount): bool
    {
        if (!$bankAccount) {
            return false;
        }

        // Staff users cannot access bank accounts
        if ($this->isStaff()) {
            return false;
        }

        // Admins can access all bank accounts
        if ($this->isAdmin()) {
            return true;
        }
        
        // Editors can access bank accounts they created
        if ($this->isEditor()) {
            return $bankAccount->user_id === $this->id;
        }
        
        // Viewers can view bank accounts they created
        if ($this->isViewer()) {
            return $bankAccount->user_id === $this->id;
        }
        
        return false;
    }

    /**
     * Relationship with daily sales
     */
    public function dailySales()
    {
        return $this->hasMany(DailySale::class);
    }

    /**
     * Relationship with daily fuels
     */
    public function dailyFuels()
    {
        return $this->hasMany(DailyFuel::class);
    }

    /**
     * Relationship with transactions
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
