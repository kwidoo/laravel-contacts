<?php

namespace Kwidoo\Contacts\Contracts;

interface VerifierFactory
{
    public function make(string $type): Verifier;
}
