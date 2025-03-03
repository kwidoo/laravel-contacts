<?php

namespace Kwidoo\Contacts\Contracts;

interface Contactable
{
    public function getKey();
    public function getMorphClass();
    public function contacts();
}
