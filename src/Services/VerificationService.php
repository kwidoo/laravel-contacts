<?php

namespace Kwidoo\Contacts\Services;

use Kwidoo\Contacts\Aggregates\ContactAggregateRoot;
use Kwidoo\Contacts\Contracts\Contact;
use Kwidoo\Contacts\Contracts\VerificationService as VerificationServiceContract;
use Kwidoo\Contacts\Contracts\Verifier;

class VerificationService implements VerificationServiceContract
{
    public function __construct(
        protected Verifier $verifier,
        protected Contact $contact,
    ) {}

    /**
     * @param string|null $token
     *
     * @return void
     */
    public function create(): void
    {
        ContactAggregateRoot::retrieve($this->contact->getKey())
            ->startVerification($this->contact->getKey(), get_class($this->verifier))
            ->persist();
        $this->verifier->create();
    }

    /**
     * @param string $token
     *
     * @return bool
     */
    public function verify(string $token): bool
    {
        $verified = $this->verifier->verify($token);

        if ($verified) {
            ContactAggregateRoot::retrieve($this->contact->getKey())
                ->verifyContact($this->contact->getKey(), get_class($this->verifier))
                ->persist();
        }

        return $verified;
    }
}
