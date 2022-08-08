<?php

namespace Kwidoo\Contacts\Traits;

use Exception;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Kwidoo\Contacts\Contracts\Item;
use Kwidoo\Contacts\Models\Contact;

/**
 * Class HasContacts
 * @package Kwidoo\Contacts\Traits
 * @property Collection|Contact[]  $contacts
 */
trait HasContacts
{
    /**
     * Get all contacts for this model.
     *
     * @return MorphMany
     */
    public function contacts(): MorphOne
    {
        return $this->morphOne(Contact::class, 'contactable')->latestOfMany();
    }

    /**
     * Check if model has contacts.
     *
     * @return bool
     */
    public function hasContacts(): bool
    {
        return (bool) $this->contacts->count();
    }

    /**
     * Add a contact to this model.
     *
     * @param  array  $attributes
     * @return mixed
     * @throws Exception
     */
    public function addContact($item)
    {

        $count = $this->contacts->count();

        if ($item instanceof Item) {
            $this->contacts->push($item);
        }
        if (is_string($item)) {
            $this->contacts->email = $item;
        }
        if (is_array($item)) {
            $key = array_keys($item)[0];
            if ($key === 0) {
                $key = 'email';
            }
            $this->contacts->$key = $item;
        }
        if ($count < $this->contacts->count()) {
            $this->contacts->save();
        }
        return $this->contacts;
    }

    /**
     * Deletes all the contacts of this model.
     *
     * @return bool
     */
    public function flushContacts(): void
    {
        $this->contacts->delete();
    }
}
