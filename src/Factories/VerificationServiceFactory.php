<?php

namespace Kwidoo\Contacts\Factories;

use InvalidArgumentException;
use Kwidoo\Contacts\Contracts\Contact;
use Kwidoo\Contacts\Contracts\VerificationContext;
use Kwidoo\Contacts\Contracts\VerificationService;
use Kwidoo\Contacts\Contracts\VerificationServiceFactory as VerificationServiceFactoryContract;

class VerificationServiceFactory implements VerificationServiceFactoryContract
{
    public function __construct(protected VerifierFactory $verifierFactory) {}

    public function make(
        Contact $contact,
        VerificationContext $context,
        ?string $aggregateName = null
    ): VerificationService {
        $available = config('contacts.verifiers', []);
        if (!array_key_exists($contact->type, $available)) {
            throw new InvalidArgumentException("Unsupported contact type: {$contact->type}");
        }

        $verifier = $this->verifierFactory->make($contact->type, $context);

        return app()->make(VerificationService::class, [
            'verifier' => $verifier,
            'contact' => $contact,
            '$aggregate' => $aggregateName ? app()->make($aggregateName) : null
        ]);
    }
}
