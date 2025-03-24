<?php

namespace Kwidoo\Contacts\Contracts;

interface VerificationContext
{
    public function getTemplate(Contact $contact): string;
}
