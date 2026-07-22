<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetOtp extends Model
{
    protected $fillable = [
        'email',
        'otp',
        'token',
        'expires_at',
        'used_at',
        'completed_at',
        'failed_attempts',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_attempts' => 'integer',
        ];
    }
}
