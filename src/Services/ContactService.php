<?php

namespace Kwidoo\Contacts\Services;

use Kwidoo\Contacts\Aggregates\ContactAggregateRoot;
use Kwidoo\Contacts\Contracts\ContactService as ContactServiceContract;
use Kwidoo\Contacts\Models\Contact;
use Kwidoo\Contacts\Exceptions\ContactServiceException;
use Kwidoo\Contacts\Contracts\Contactable;
use Illuminate\Support\Str;
use Kwidoo\Contacts\Contracts\ContactAggregate;

/**
 * @property ContactAggregateRoot $aggregate
 */
class ContactService implements ContactServiceContract
{
    public function __construct(
        protected ContactAggregate $aggregate,
        protected Contactable $model
    ) {}

    /**
     * Create a new contact.
     *
     * @param string $type
     * @param string $value
     * @return string|int
     */
    public function create(string $type, string $value): string|int
    {
        $allowedTypes = array_keys(config('contacts.verifiers'));

        if (!in_array($type, $allowedTypes, true)) {
            throw new ContactServiceException("Invalid contact type: {$type}");
        }

        $id = Str::uuid()->toString();
        $this->aggregate->retrieve($id)
            ->createContact($this->model, $id, $type, $value)
            ->persist();

        return $id;
    }

    /**
     * Remove a contact (soft delete).
     *
     * @param Contact $contact
     * @return bool
     * @throws ContactServiceException
     */
    public function delete(string $id): bool
    {
        $contact = $this->model->contacts()
            ->where('id', $id)
            ->firstOrFail();

        if ($contact->is_primary) {
            throw new ContactServiceException("Can't delete primary contact.");
        }

        $this->aggregate->retrieve($contact->getKey())
            ->deleteContact($contact->getKey())
            ->persist();

        return true;
    }

    /**
     * Restore a soft-deleted contact by UUID.
     *
     * @param string $id
     * @return bool
     */
    public function restore(string $id): bool
    {
        $contact = $this->model->contacts()
            ->onlyTrashed()
            ->where('id', $id)
            ->firstOrFail();

        $restored = (bool) $contact->restore();
        if ($contact->is_primary) {
            // If it was primary before, make it non-primary after restore
            $contact->update(['is_primary' => false]);
        }

        return $restored;
    }
}
