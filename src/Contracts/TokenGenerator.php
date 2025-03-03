<?php

namespace Kwidoo\Contacts\Contracts;

use Illuminate\Database\Eloquent\Model;

interface TokenGenerator
{
    public function generate(): Model;
}
