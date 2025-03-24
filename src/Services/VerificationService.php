<?php

namespace Kwidoo\Contacts\Services;

use Kwidoo\Contacts\Contracts\Contact;
use Kwidoo\Contacts\Contracts\ContactAggregate;
use Kwidoo\Contacts\Contracts\VerificationService as VerificationServiceContract;
use Kwidoo\Contacts\Contracts\Verifier;

/**
 * @property \Kwidoo\Contacts\Aggregates\ContactAggregateRoot aggregate
 **/
class VerificationService implements VerificationServiceContract
{
    public function __construct(
        protected Verifier $verifier,
        protected Contact $contact,
        protected ContactAggregate $aggregate
    ) {}

    /**
     * @param string|null $token
     *
     * @return void
     */
    public function create(): void
    {
        $this->aggregate->retrieve($this->contact->getKey())
            ->startVerification($this->contact->getKey(), get_class($this->verifier))
            ->persist();

        $this->verifier->create($this->contact);
    }

    /**
     * @param string $token
     *
     * @return bool
     */
    public function verify(string $token): bool
    {
        $verified = $this->verifier->verify($this->contact, $token);

        if ($verified) {
            $this->markVerified();
        }

        return $verified;
    }

    /**
     * @return void
     */
    public function markVerified(): void
    {
        $this->aggregate->retrieve($this->contact->getKey())
            ->verifyContact($this->contact->getKey(), get_class($this->verifier))
            ->persist();
    }
}
