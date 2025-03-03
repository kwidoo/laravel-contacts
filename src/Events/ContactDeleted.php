<?php

namespace Kwidoo\Contacts\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class ContactDeleted extends ShouldBeStored
{
    public function __construct(
        public string|int $contactUuid,
    ) {}
}
