<?php

namespace Kwidoo\Contacts\Contracts;

interface VerificationService
{
    public function create(): void;

    public function verify(string $token): bool;

    public function markVerified(): void;
}
