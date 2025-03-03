<?php

namespace Kwidoo\Contacts\Services;

use Kwidoo\Contacts\Contracts\ContactService as ContactServiceContract;
use Kwidoo\Contacts\Models\Contact;
use Kwidoo\Contacts\Exceptions\ContactServiceException;
use Kwidoo\Contacts\Contracts\Contactable;

class ContactService implements ContactServiceContract
{
    public function __construct(
        protected Contactable $model
    ) {}

    /**
     * Create a new contact.
     *
     * @param string $type
     * @param string $value
     * @return Contact
     */
    public function create(string $type, string $value): Contact
    {
        $allowedTypes = array_keys(config('contacts.verifiers'));

        if (!in_array($type, $allowedTypes, true)) {
            throw new ContactServiceException("Invalid contact type: {$type}");
        }

        return $this->model->contacts()->create([
            'type'        => $type,
            'value'       => $value,
            'is_primary'  => false,
            'is_verified' => false,
        ]);
    }

    /**
     * Remove a contact (soft delete).
     *
     * @param Contact $contact
     * @return bool
     * @throws ContactServiceException
     */
    public function destroy(Contact $contact): bool
    {
        if ($contact->is_primary) {
            throw new ContactServiceException("Can't delete primary contact.");
        }

        return (bool) $contact->delete();
    }

    /**
     * Restore a soft-deleted contact by UUID.
     *
     * @param string $uuid
     * @return bool
     */
    public function restore(string $uuid): bool
    {
        $contact = $this->model->contacts()
            ->onlyTrashed()
            ->where('uuid', $uuid)
            ->firstOrFail();

        $restored = (bool) $contact->restore();
        if ($contact->is_primary) {
            // If it was primary before, make it non-primary after restore
            $contact->update(['is_primary' => false]);
        }

        return $restored;
    }
}
