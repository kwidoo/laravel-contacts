<?php

namespace Kwidoo\Contacts\Aggregates;

use Kwidoo\Contacts\Contracts\Contactable;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;
use Kwidoo\Contacts\Events\ContactCreated;
use Kwidoo\Contacts\Events\ContactVerified;
use Kwidoo\Contacts\Events\ContactDeleted;
use Kwidoo\Contacts\Events\PrimaryChanged;
use Kwidoo\Contacts\Events\StartVerification;

class ContactAggregateRoot extends AggregateRoot
{
    public function createContact(
        Contactable $contactable,
        string $contactUuid,
        string $type,
        string $value
    ): self {
        $identifier = $contactable->getKey();
        $this->recordThat(
            new ContactCreated($identifier, $contactable->getMorphClass(), $contactUuid, $type, $value)
        );

        return $this;
    }

    /**
     * @param string $contactUuid
     * @param string $verifier
     *
     * @return self
     */
    public function startVerification(string $contactUuid, string $verifier): self
    {
        $this->recordThat(new StartVerification($contactUuid, $verifier));

        return $this;
    }

    /**
     * @param string $contactUuid
     * @param string $verifier
     *
     * @return self
     */
    public function verifyContact(string $contactUuid, string $verifier): self
    {
        $this->recordThat(new ContactVerified($contactUuid, $verifier));

        return $this;
    }

    /**
     * @param string $contactUuid
     *
     * @return self
     */
    public function deleteContact(string $contactUuid): self
    {
        $this->recordThat(new ContactDeleted($contactUuid));

        return $this;
    }

    /**
     * Update the primary contact by recording both the previous and new primary contact.
     *
     * @param string $oldContactUuid
     * @param string $newContactUuid
     *
     * @return self
     */
    public function changePrimary(string $oldContactUuid, string $newContactUuid): self
    {
        $this->recordThat(new PrimaryChanged($oldContactUuid, $newContactUuid));

        return $this;
    }
}
