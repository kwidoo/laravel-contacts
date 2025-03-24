<?php

namespace Kwidoo\Contacts\Contracts;

interface Verifier
{
    public function create(Contact $contact): void;

    public function verify(Contact $contact, string $token): bool;
}
