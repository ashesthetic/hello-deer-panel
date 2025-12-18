<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleToken extends Model
{
    protected $fillable = [
        'user_id',
        'service',
        'access_token',
        'refresh_token',
        'expires_at',
        'token_data',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'token_data' => 'array',
    ];

    /**
     * Get the user that owns the token
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the access token is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Get the token array in the format expected by Google Client
     */
    public function toGoogleTokenArray(): array
    {
        $tokenArray = [
            'access_token' => $this->access_token,
            'expires_in' => $this->expires_at->diffInSeconds(now()),
        ];

        if ($this->refresh_token) {
            $tokenArray['refresh_token'] = $this->refresh_token;
        }

        // Merge any additional token data
        if ($this->token_data) {
            $tokenArray = array_merge($tokenArray, $this->token_data);
        }

        return $tokenArray;
    }

    /**
     * Create or update token from Google token array
     */
    public static function createFromGoogleToken($userId, array $tokenData, string $service = 'google_drive'): self
    {
        $expiresAt = now()->addSeconds($tokenData['expires_in'] ?? 3600);

        return static::updateOrCreate(
            ['user_id' => $userId, 'service' => $service],
            [
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'expires_at' => $expiresAt,
                'token_data' => $tokenData,
            ]
        );
    }
}
