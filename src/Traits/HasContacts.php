<?php

namespace Kwidoo\Contacts\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Kwidoo\Contacts\Contracts\Contact;

trait HasContacts
{
    /**
     * @return MorphMany
     */
    public function contacts(): MorphMany
    {
        return $this->morphMany(config('contacts.model'), 'contactable');
    }

    /**
     * @return Contact|null
     */
    public function getPrimaryContactAttribute(): ?Contact
    {
        return $this->contacts()->where('is_primary', true)->first();
    }
}
