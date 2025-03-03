<?php

namespace Kwidoo\Contacts\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class StartVerification extends ShouldBeStored
{
    public function __construct(
        public string|int $contactUuid,
        public string $verifier,
    ) {}
}
