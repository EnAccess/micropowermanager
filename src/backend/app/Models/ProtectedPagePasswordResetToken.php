<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Class ProtectedPagePasswordResetToken.
 *
 * @property int    $id
 * @property string $email
 * @property string $token
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $expires_at
 */
class ProtectedPagePasswordResetToken extends BaseModel {
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'token',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Generate a new reset token for the given email.
     */
    public static function generateToken(string $email): self {
        // Delete any existing tokens for this email
        ProtectedPagePasswordResetToken::where('email', $email)->delete();

        // Create new token with 1 hour expiration
        return ProtectedPagePasswordResetToken::create([
            'email' => $email,
            'token' => Str::random(64),
            'expires_at' => now()->addHour(),
        ]);
    }

    /**
     * Find a valid token by email and token string.
     */
    public static function findValidToken(string $email, string $token): ?self {
        return ProtectedPagePasswordResetToken::where('email', $email)
            ->where('token', $token)
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * Check if the token is expired.
     */
    public function isExpired(): bool {
        return $this->expires_at->isPast();
    }
}
