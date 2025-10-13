<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * @property string      $email
 * @property string      $token
 * @property Carbon|null $created_at
 */
class PasswordResetToken extends BaseModel {
    protected $table = 'password_resets';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'token',
        'created_at',
    ];

    /**
     * Generate a new reset token for the given email.
     */
    public static function generateToken(string $email): self {
        static::query()->where('email', $email)->delete();

        return static::query()->create([
            'email' => $email,
            'token' => Str::random(64),
            'created_at' => now(),
        ]);
    }
}
