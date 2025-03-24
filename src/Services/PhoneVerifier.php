<?php

namespace Kwidoo\Contacts\Services;

use Kwidoo\Contacts\Contracts\Contact;
use Kwidoo\Contacts\Contracts\TokenGenerator;
use Kwidoo\Contacts\Contracts\VerificationContext;
use Kwidoo\Contacts\Contracts\Verifier;
use Kwidoo\SmsVerification\Contracts\VerifierInterface;

class PhoneVerifier implements Verifier
{
    public function __construct(
        protected TokenGenerator $tokenGenerator,
        protected VerificationContext $context,
        protected VerifierInterface $phoneService
    ) {
        //
    }

    /**
     * Send OTP to the phone number from $contact->value.
     *
     * @return void
     */
    public function create(Contact $contact): void
    {
        $this->phoneService->create($contact->value);
    }

    /**
     * Verify the code for the phone number from $contact->value.
     *
     * @param string $token
     * @return bool
     */
    public function verify(Contact $contact, string $token): bool
    {
        return $this->phoneService->validate([
            $contact->value,
            $token
        ]);
    }
}
