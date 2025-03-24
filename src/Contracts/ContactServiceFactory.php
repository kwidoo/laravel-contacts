<?php

namespace Kwidoo\Contacts\Contracts;

use Kwidoo\Contacts\Contracts\Contactable;
use Kwidoo\Contacts\Contracts\ContactService;

interface ContactServiceFactory
{
    public function make(Contactable $type): ContactService;
}
