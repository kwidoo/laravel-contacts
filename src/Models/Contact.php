<?php

namespace Kwidoo\Contacts\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Kwidoo\Contacts\Contracts\Contact as ContactContract;
use Kwidoo\Contacts\Exceptions\DuplicateContactException;
use Illuminate\Support\Str;
use Spatie\EventSourcing\Projections\Projection;

class Contact extends Projection implements ContactContract
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'contactable_type',
        'contactable_id',
        'type',
        'value',
        'is_primary',
        'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_verified' => 'boolean',
        ];
    }

    /**
     * Polymorphic relationship to the parent model (e.g. User).
     */
    public function contactable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany
     */
    public function tokens(): HasMany
    {
        return $this->hasMany(config('contacts.token.model'));
    }

    /**
     * Helper to check if contact is primary.
     */
    public function isPrimary(): bool
    {
        return (bool) $this->is_primary;
    }

    /**
     * Helper to check if contact is verified.
     */
    public function isVerified(): bool
    {
        return (bool) $this->is_verified;
    }

    public function getTable()
    {
        return config('contacts.table');
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

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (config('contacts.uuid') && empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }

            $duplicateExists = self::whereHasMorph(
                'contactable',
                $model->contactable->getMorphClass(),
                fn($query) =>
                $query->where($model->contactable->getKeyName(), $model->contactable_id)
            )->where('value', $model->value)
                ->where('type', $model->type)
                ->whereNull('deleted_at')
                ->exists();

            if ($duplicateExists) {
                throw new DuplicateContactException("{$model->type} {$model->value} already exists for this entity.");
            }

            $model->is_primary = !self::whereHasMorph(
                'contactable',
                $model->contactable->getMorphClass(),
                fn($query) =>
                $query->where($model->contactable->getKeyName(), $model->contactable_id)
            )->exists();
        });
    }
}
