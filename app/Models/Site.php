<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class Site extends Model
{
    use HasFactory, HasUuids, Notifiable;

    protected $fillable = [
        'name',
        'url',
        'key',
        'description',
        'receive_email',
        'slack_webhook',
        'discord_webhook',
        'last_exception_at',
        'check_url',
        'enable_uptime_check',
        'email_outage',
        'email_resolved',
        'checked_at',
        'is_online',
    ];

    protected $casts = [
        'receive_email' => 'boolean',
        'last_exception_at' => 'datetime',
        'enable_uptime_check' => 'boolean',
        'checked_at' => 'datetime',
        'is_online' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($site) {
            if (empty($site->key)) {
                $site->key = Str::random(32);
            }
        });
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(Exception::class);
    }

    public function unreadExceptions(): HasMany
    {
        return $this->exceptions()->where('status', Exception::OPEN);
    }

    public function outages(): HasMany
    {
        return $this->hasMany(Outage::class);
    }

    public function activeOutage(): ?Outage
    {
        return $this->outages()->whereNull('resolved_at')->latest('occurred_at')->first();
    }

    public function isDown(): bool
    {
        return ! $this->is_online;
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'site_user')
            ->withPivot('is_owner')
            ->withTimestamps();
    }

    public function routeNotificationForSlack()
    {
        return $this->slack_webhook;
    }

    public function routeNotificationForDiscord()
    {
        return $this->discord_webhook;
    }

    public function getInstallInstructions(): string
    {
        return <<<INSTRUCTIONS
## Install Laracheck Client

1. Install the package via composer:
```bash
composer require officineweb/laracheck-client
```

2. Publish the configuration file:
```bash
php artisan vendor:publish --tag=laracheck-config
```

3. Add your site key to your `.env` file:
```
LARACHECK_KEY={$this->key}
LARACHECK_URL={$this->url}
```

4. That's it! Exceptions will now be automatically tracked.

For more information, visit: https://github.com/officineweb/laracheck
INSTRUCTIONS;
    }
}
