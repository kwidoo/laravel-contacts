<?php

namespace Kwidoo\Contacts\Contracts;

use Kwidoo\Contacts\Contracts\VerificationService;

interface VerificationServiceFactory
{
    public function make(
        Contact $contact
    ): VerificationService;
}
