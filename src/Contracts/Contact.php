<?php

namespace Kwidoo\Contacts\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphTo;

interface Contact
{
    public function contactable(): MorphTo;
    public function isVerified(): bool;
    public function isPrimary(): bool;
}
