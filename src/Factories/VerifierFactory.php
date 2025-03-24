<?php

namespace Kwidoo\Contacts\Factories;

use InvalidArgumentException;
use Kwidoo\Contacts\Contracts\VerificationContext;
use Kwidoo\Contacts\Contracts\Verifier;
use Kwidoo\Contacts\Contracts\VerifierFactory as VerifierFactoryContract;

class VerifierFactory implements VerifierFactoryContract
{
    public function make(string $type, VerificationContext $context): Verifier
    {
        $available = config('contacts.verifiers', []);
        if (!array_key_exists($type, $available)) {
            throw new InvalidArgumentException("Unsupported verifier type: {$type}");
        }

        return app()->make($available[$type], ['context' => $context]);
    }
}
