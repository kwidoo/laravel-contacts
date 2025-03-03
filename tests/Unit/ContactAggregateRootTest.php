<?php

namespace Kwidoo\Contacts\Tests\Unit;

use Kwidoo\Contacts\Aggregates\ContactAggregateRoot;
use Kwidoo\Contacts\Events\ContactCreated;
use Kwidoo\Contacts\Events\StartVerification;
use Kwidoo\Contacts\Events\ContactVerified;
use Kwidoo\Contacts\Events\ContactDeleted;
use Kwidoo\Contacts\Events\PrimaryChanged;
use Kwidoo\Contacts\Tests\Fakes\FakeContactable;
use Kwidoo\Contacts\Tests\TestCase;

class ContactAggregateRootTest extends TestCase
{
    /**
     * Helper to access uncommitted events.
     * (Assumes your AggregateRoot exposes a method getUncommittedEvents() for testing.)
     */
    protected function getEvents(ContactAggregateRoot $aggregate): array
    {
        return $aggregate->getUncommittedEvents();
    }

    public function testCreateContactRecordsContactCreatedEvent()
    {
        $identifier  = 1;
        $contactUuid = 'uuid-123';
        $type        = 'email';
        $value       = 'test@example.com';

        $fakeContactable = new FakeContactable($identifier);

        $aggregate = ContactAggregateRoot::retrieve($identifier)
            ->createContact($fakeContactable, $contactUuid, $type, $value);

        $events = $this->getEvents($aggregate);
        $this->assertCount(1, $events);

        $event = $events[0];
        $this->assertInstanceOf(ContactCreated::class, $event);
        $this->assertEquals($identifier, $event->identifier);
        $this->assertEquals('FakeContactable', $event->class);
        $this->assertEquals($contactUuid, $event->contactUuid);
        $this->assertEquals($type, $event->type);
        $this->assertEquals($value, $event->value);
    }

    public function testStartVerificationRecordsStartVerificationEvent()
    {
        $contactUuid = 'uuid-123';
        $verifier    = 'FakeVerifier';

        $aggregate = ContactAggregateRoot::retrieve(1)
            ->startVerification($contactUuid, $verifier);

        $events = $this->getEvents($aggregate);
        $this->assertCount(1, $events);

        $event = $events[0];
        $this->assertInstanceOf(StartVerification::class, $event);
        $this->assertEquals($contactUuid, $event->contactUuid);
        $this->assertEquals($verifier, $event->verifier);
    }

    public function testVerifyContactRecordsContactVerifiedEvent()
    {
        $contactUuid = 'uuid-123';
        $verifier    = 'FakeVerifier';

        $aggregate = ContactAggregateRoot::retrieve(1)
            ->verifyContact($contactUuid, $verifier);

        $events = $this->getEvents($aggregate);
        $this->assertCount(1, $events);

        $event = $events[0];
        $this->assertInstanceOf(ContactVerified::class, $event);
        $this->assertEquals($contactUuid, $event->contactUuid);
        $this->assertEquals($verifier, $event->verifier);
    }

    public function testDeleteContactRecordsContactDeletedEvent()
    {
        $contactUuid = 'uuid-123';

        $aggregate = ContactAggregateRoot::retrieve(1)
            ->deleteContact($contactUuid);

        $events = $this->getEvents($aggregate);
        $this->assertCount(1, $events);

        $event = $events[0];
        $this->assertInstanceOf(ContactDeleted::class, $event);
        $this->assertEquals($contactUuid, $event->contactUuid);
    }

    public function testChangePrimaryRecordsPrimaryChangedEvent()
    {
        $oldUuid = 'old-uuid';
        $newUuid = 'new-uuid';

        $aggregate = ContactAggregateRoot::retrieve(1)
            ->changePrimary($oldUuid, $newUuid);

        $events = $this->getEvents($aggregate);
        $this->assertCount(1, $events);

        $event = $events[0];
        $this->assertInstanceOf(PrimaryChanged::class, $event);
        $this->assertEquals($oldUuid, $event->oldContactUuid);
        $this->assertEquals($newUuid, $event->newContactUuid);
    }
}
