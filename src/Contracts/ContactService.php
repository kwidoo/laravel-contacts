<?php

namespace Kwidoo\Contacts\Contracts;

use Kwidoo\Contacts\Models\Contact;

interface ContactService
{
    public function create(string $type, string $value): Contact;

    public function destroy(Contact $contact): bool;

    public function restore(string $uuid): bool;
}
