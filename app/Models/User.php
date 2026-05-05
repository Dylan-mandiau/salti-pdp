<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'city', 'address', 'phone', 'require_otp_by_default', 'access_plan_path', 'access_plan_filename'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_AGENCY = 'agency';
    public const ROLE_QSE_ADMIN = 'qse_admin';

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'require_otp_by_default' => 'boolean',
        ];
    }

    public function isQseAdmin(): bool
    {
        return $this->role === self::ROLE_QSE_ADMIN;
    }

    public function pdps(): HasMany
    {
        return $this->hasMany(Pdp::class, 'agency_id');
    }

    public function prestataires(): HasMany
    {
        return $this->hasMany(Prestataire::class, 'agency_id');
    }
}
