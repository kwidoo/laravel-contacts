<?php

namespace Kwidoo\Contacts\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class PrimaryChanged extends ShouldBeStored
{
    public function __construct(
        public string|int $oldContactUuid,
        public string|int $newContactUuid,
    ) {}
}
