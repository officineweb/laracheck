<?php

namespace App\Models;

use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasEmailAuthentication, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'receive_email',
        'date_format',
        'timezone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'api_token',
        'app_authentication_secret',
        'app_authentication_recovery_codes',
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
            'is_admin' => 'boolean',
            'receive_email' => 'boolean',
            'app_authentication_recovery_codes' => 'encrypted:array',
            'has_email_authentication' => 'boolean',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            $user->api_token = \Illuminate\Support\Str::random(60);
        });
    }

    public function sites()
    {
        return $this->belongsToMany(Site::class, 'site_user')
            ->withPivot('is_owner')
            ->withTimestamps();
    }

    public function ownedSites()
    {
        return $this->sites()->wherePivot('is_owner', true);
    }

    public function isAdmin()
    {
        return $this->is_admin;
    }

    public function getGravatar($size = 150)
    {
        return 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($this->email))) . '?s=' . (int) $size;
    }

    public function formatDate($date, $withTime = true): string
    {
        if (! $date) {
            return 'N/A';
        }

        if (! $date instanceof \Carbon\Carbon) {
            $date = \Carbon\Carbon::parse($date);
        }

        $date = $date->setTimezone($this->timezone ?? 'UTC');

        $format = $this->date_format ?? 'd/m/Y';

        if ($withTime) {
            $format .= ' H:i:s';
        }

        return $date->format($format);
    }

    // Filament User Interface
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    // App Authentication Interface
    public function hasAppAuthentication(): bool
    {
        return filled($this->app_authentication_secret);
    }

    public function getAppAuthenticationSecret(): ?string
    {
        return $this->app_authentication_secret;
    }

    public function setAppAuthenticationSecret(?string $secret): void
    {
        $this->app_authentication_secret = $secret;
        $this->save();
    }

    public function saveAppAuthenticationSecret(?string $secret): void
    {
        $this->setAppAuthenticationSecret($secret);
    }

    public function getAppAuthenticationHolderName(): string
    {
        return $this->name ?? $this->email;
    }

    public function getAppAuthenticationRecoveryCodes(): ?array
    {
        return $this->app_authentication_recovery_codes;
    }

    public function setAppAuthenticationRecoveryCodes(?array $codes): void
    {
        $this->app_authentication_recovery_codes = $codes;
        $this->save();
    }

    // Email Authentication Interface
    public function hasEmailAuthentication(): bool
    {
        return (bool) $this->has_email_authentication;
    }

    public function toggleEmailAuthentication(bool $condition): void
    {
        $this->has_email_authentication = $condition;
        $this->save();
    }
}
