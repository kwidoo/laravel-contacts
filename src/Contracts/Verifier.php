<?php

namespace Kwidoo\Contacts\Contracts;

interface Verifier
{
    public function create(): void;

    public function verify(string $token): bool;
}
