<?php

namespace Kwidoo\Contacts\Projectors;

use Exception;
use Illuminate\Database\Eloquent\Relations\Relation;
use Kwidoo\Contacts\Contracts\MustVerify;
use Kwidoo\Contacts\Contracts\ContactRepository;
use Kwidoo\Contacts\Contracts\VerificationServiceFactory;
use Kwidoo\Contacts\Events\ContactCreated;
use Kwidoo\Contacts\Events\ContactDeleted;
use Kwidoo\Contacts\Events\ContactVerified;
use Kwidoo\Contacts\Events\PrimaryChanged;
use Kwidoo\Contacts\Factories\RegistrationContext;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;

class ContactProjector extends Projector
{
    public array $handlesEvents = [
        ContactCreated::class => 'onContactCreated',
        ContactDeleted::class => 'onContactDeleted',
        ContactVerified::class => 'onContactVerified',
        PrimaryChanged::class => 'onPrimaryChanged',
    ];

    public function __construct(
        protected ContactRepository $repository,
        protected VerificationServiceFactory $factory
    ) {}

    /**
     * @param ContactCreated $event
     *
     * @return void
     */
    public function onContactCreated(ContactCreated $event): void
    {
        $class = Relation::getMorphedModel($event->class) ?? $event->class;

        $model = $class::find($event->identifier);
        if (!$model) {
            throw new Exception('Model not found');
        }

        $contact = $this->repository->createContact([
            ...($event->contactUuid ? ['uuid' => $event->contactUuid] : []),
            'type' => $event->type,
            'value' => $event->value,
            'is_primary' => false,
            'is_verified' => false,
            'contactable_id' => $event->identifier,
            'contactable_type' => $model->getMorphClass(),
        ]);

        if ($model instanceof MustVerify || config('iam.should_verify')) {
            $verificationContext = new RegistrationContext($contact);
            $verificationService = $this->factory->make($contact, $verificationContext);
            $verificationService->create();
        }
    }

    /**
     * @param ContactDeleted $event
     *
     * @return void
     */
    public function onContactDeleted(ContactDeleted $event): void
    {
        $contact = $this->repository->find($event->contactUuid);
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
        $contact = $this->repository->find($event->contactUuid);
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
        $oldPrimary = $this->repository->find($event->oldContactUuid);
        $newPrimary = $this->repository->find($event->newContactUuid);

        if (!$oldPrimary || ! $newPrimary) {
            throw new Exception('Contact not found');
        }

        $oldPrimary->writeable()->update(['is_primary' => false]);
        $newPrimary->writeable()->update(['is_primary' => true]);
    }
}
