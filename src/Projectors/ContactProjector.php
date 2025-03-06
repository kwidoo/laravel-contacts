<?php

namespace Kwidoo\Contacts\Projectors;

use Exception;
use Illuminate\Database\Eloquent\Relations\Relation;
use Kwidoo\Contacts\Events\ContactCreated;
use Kwidoo\Contacts\Events\ContactDeleted;
use Kwidoo\Contacts\Events\ContactVerified;
use Kwidoo\Contacts\Events\PrimaryChanged;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;

class ContactProjector extends Projector
{
    public array $handlesEvents = [
        ContactCreated::class => 'onContactCreated',
        ContactDeleted::class => 'onContactDeleted',
        ContactVerified::class => 'onContactVerified',
        PrimaryChanged::class => 'onPrimaryChanged',

    ];

    /**
     * @param ContactCreated $event
     *
     * @return void
     */
    public function onContactCreated(ContactCreated $event): void
    {
        $class = Relation::getMorphedModel($event->class) ?? $event->class;

        $model = ($class)::find($event->identifier);
        $contact = $model->contacts()->make([
            ...($event->contactUuid ? ['uuid' => $event->contactUuid] : []),
            'type' => $event->type,
            'value' => $event->value,
            'is_primary' => false,
            'is_verified' => false,
        ]);

        $contact->writeable()->save();
    }

    /**
     * @param ContactDeleted $event
     *
     * @return void
     */
    public function onContactDeleted(ContactDeleted $event): void
    {
        $contact = config('contacts.model')::find($event->contactUuid);
        if ($contact) {
            $contact->writeable()->delete();
        }
    }

    /**
     * @param ContactVerified $event
     *
     * @return void
     */
    public function onContactVerified(ContactVerified $event): void
    {
        $contact = config('contacts.model')::find($event->contactUuid);
        if ($contact) {
            $contact->writeable()->update(['is_verified' => true]);
        }
    }

    /**
     * @param PrimaryChanged $event
     *
     * @return void
     */
    public function onPrimaryChanged(PrimaryChanged $event): void
    {
        $oldPrimary = config('contacts.model')::find($event->oldContactUuid);
        $newPrimary = config('contacts.model')::find($event->newContactUuid);

        if (!$oldPrimary || ! $newPrimary) {
            throw new Exception('Contact not found');
        }

        $oldPrimary->writeable()->update(['is_primary' => false]);
        $newPrimary->writeable()->update(['is_primary' => true]);
    }
}
