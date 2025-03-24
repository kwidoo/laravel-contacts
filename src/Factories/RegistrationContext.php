<?php

namespace Kwidoo\Contacts\Factories;

use Kwidoo\Contacts\Contracts\Contact;
use Kwidoo\Contacts\Notifications\TokenNotification;
use Kwidoo\Contacts\Contracts\VerificationContext;

class RegistrationContext implements VerificationContext
{
    public function getTemplate(Contact $contact): string
    {
        if ($contact->type === 'phone') {
            return 'phone_verification';
        }
        return TokenNotification::class;
    }
}
