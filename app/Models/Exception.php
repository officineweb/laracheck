<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exception extends Model
{
    use HasFactory, HasUuids;

    const OPEN = 'OPEN';

    const FIXED = 'FIXED';

    protected $guarded = [];

    protected $casts = [
        'user' => 'array',
        'storage' => 'array',
        'executor' => 'array',
        'additional' => 'array',
        'mailed' => 'boolean',
        'published_at' => 'datetime',
        'notified_at' => 'datetime',
    ];

    protected $appends = [
        'short_exception_text',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function occurences()
    {
        return $this->hasMany(self::class, 'exception', 'exception')
            ->where('id', '!=', $this->id)
            ->where('line', $this->line)
            ->where('site_id', $this->site_id)
            ->where('created_at', '>', now()->subMonth());
    }

    public function getShortExceptionTextAttribute()
    {
        if (! $this->exception) {
            return '-No exception text-';
        }

        return \Illuminate\Support\Str::limit($this->exception, 75);
    }

    public function scopeNotMailed($query)
    {
        return $query->where('mailed', false);
    }

    public function scopeNew($query)
    {
        return $query->where('status', self::OPEN);
    }

    public function markAsRead()
    {
        $this->status = self::READ;
        $this->save();
    }

    public function markAs($status = self::FIXED)
    {
        $this->status = $status;
        $this->save();
    }

    public function markAsMailed()
    {
        $this->mailed = true;
        $this->save();
    }

    public function makePublic()
    {
        $this->publish_hash = \Illuminate\Support\Str::random(15);
        $this->published_at = now();
        $this->save();

        return $this;
    }

    public function removePublic()
    {
        $this->publish_hash = null;
        $this->published_at = null;
        $this->save();

        return $this;
    }

    public function isPublic()
    {
        return (bool) $this->publish_hash;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($exception) {
            // Set default status only if not already set
            if (! $exception->status) {
                $exception->status = self::OPEN;
            }
        });

        static::created(function ($exception) {
            // Don't send notifications for auto-fixed exceptions (4xx errors)
            if ($exception->status === self::FIXED) {
                // Mark as notified immediately to exclude from batch processing
                $exception->notified_at = now();
                $exception->saveQuietly();

                return;
            }

            // Notifica database immediata agli utenti del site
            // (le notifiche email/slack/discord sono inviate in batch)
            foreach ($exception->site->users as $user) {
                $user->notify(new \App\Notifications\NewExceptionNotification($exception));
            }
        });
    }
}
