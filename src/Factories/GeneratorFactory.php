<?php

namespace Kwidoo\Contacts\Factories;

use Kwidoo\Contacts\Contracts\Contact;
use Kwidoo\Contacts\Contracts\TokenGenerator;

class GeneratorFactory
{
    public function make(Contact $contact): mixed
    {
        return app(TokenGenerator::class, ['contact' => $contact]);
    }
}
