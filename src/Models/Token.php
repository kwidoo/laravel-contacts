<?php

namespace Kwidoo\Contacts\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property bool isVerified
 * @property bool isExpired
 */
class Token extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'contact_id',
        'contact_uuid',
        'method',
        'expires_at',
        'verified_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    protected $appends = [
        'is_expired',
        'is_verified',
    ];

    /**
     * @return bool
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at ? $this->expires_at->isPast() : true;
    }

    /**
     * @return bool
     */
    public function getIsVerifiedAttribute(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * @param Builder $query
     *
     * @return void
     */
    public function scopeIsNotExpired(Builder $query): void
    {
        $query->where('expires_at', '>', now());
    }

    /**
     * @param Builder $query
     *
     * @return void
     */
    public function scopeIsNotVerified(Builder $query): void
    {
        $query->whereNull('verified_at');
    }

    /**
     * @return BelongsTo
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(config('contacts.model'));
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return config('contacts.token.table');
    }

    /**
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return config('contacts.uuid') ? 'uuid' : 'id';
    }

    /**
     * @return string
     */
    public function getKeyName(): string
    {
        return config('contacts.uuid') ? 'uuid' : 'id';
    }

    /**
     * @return bool
     */
    public function getIncrementing(): bool
    {
        return !config('contacts.uuid');
    }

    protected static function boot()
    {
        parent::boot();

        // Only generate a UUID if the configuration requires it
        if (config('contacts.uuid')) {
            static::creating(function ($model) {
                // Check if the primary key is empty before assigning
                if (empty($model->{$model->getKeyName()})) {
                    $model->{$model->getKeyName()} = (string) Str::uuid();
                }
            });
        }
    }
}
