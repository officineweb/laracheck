<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Outage extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'site_id',
        'occurred_at',
        'resolved_at',
        'response_code',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function getDurationAttribute(): string|\DateInterval
    {
        if ($this->resolved_at) {
            return $this->occurred_at->diff($this->resolved_at);
        }

        return 'In outage since '.$this->occurred_at->diffForHumans();
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    public function getStatusAttribute(): bool
    {
        return $this->isResolved();
    }
}

