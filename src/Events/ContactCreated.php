<?php

namespace Kwidoo\Contacts\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class ContactCreated extends ShouldBeStored
{
    public function __construct(
        public string|int $identifier,
        public string $class,
        public ?string $contactUuid,
        public string $type,
        public string $value,
    ) {}
}
