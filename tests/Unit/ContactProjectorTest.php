<?php

namespace Kwidoo\Contacts\Tests\Unit;

use Kwidoo\Contacts\Tests\Fixtures\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Kwidoo\Contacts\Projectors\ContactProjector;
use Kwidoo\Contacts\Events\ContactCreated;
use Kwidoo\Contacts\Events\ContactDeleted;
use Kwidoo\Contacts\Events\ContactVerified;
use Kwidoo\Contacts\Events\PrimaryChanged;
use Kwidoo\Contacts\Models\Contact;
use Exception;
use Kwidoo\Contacts\Tests\TestCase;

class ContactProjectorTest extends TestCase
{
    use RefreshDatabase;

    public function testOnContactCreated()
    {
        // Create a dummy user.
        $user = User::factory()->count(2)->create();

        // Create a ContactCreated event.
        $contactUuid = 'test-uuid-123';
        $event = new ContactCreated(
            $user->id,
            $user->getMorphClass(),
            $contactUuid,
            'email',
            'created@example.com'
        );

        $projector = new ContactProjector();
        $projector->onContactCreated($event);

        // Retrieve the newly created contact.
        $contact = $user->contacts()->where('value', 'created@example.com')->first();
        $this->assertNotNull($contact);
        $this->assertEquals('email', $contact->type);
        $this->assertFalse($contact->is_primary);
        $this->assertFalse($contact->is_verified);
        $this->assertEquals($contactUuid, $contact->uuid);
    }

    public function testOnContactDeleted()
    {
        $user = User::factory()->create();

        // Create a contact.
        $contact = Contact::factory()->make([
            'value'      => 'delete@example.com',
            'is_primary' => false,
            'is_verified' => true,
            'contactable_id' => $user->id,
            'contactable_type' => $user->getMorphClass(),
        ]);
        $contact->writeable()->save();


        $event = new ContactDeleted($contact->uuid);
        $projector = new ContactProjector();
        $projector->onContactDeleted($event);

        // Assert the contact is soft-deleted.
        $deletedContact = Contact::withTrashed()->find($contact->uuid);
        $this->assertNotNull($deletedContact);
        $this->assertNotNull($deletedContact->deleted_at);
    }

    public function testOnContactVerified()
    {
        $contact = Contact::factory()->make();
        $contact->writeable()->save();

        $event = new ContactVerified($contact->uuid, 'dummy_verifier');
        $projector = new ContactProjector();
        $projector->onContactVerified($event);

        $updatedContact = Contact::find($contact->uuid);
        $this->assertTrue($updatedContact->is_verified);
    }

    public function testOnPrimaryChangedSuccess()
    {
        $user = User::factory()->create();
        // Create two contacts.
        $oldPrimary = Contact::factory()->make();
        $oldPrimary->writeable()->save();
        $newPrimary = Contact::factory()->make();
        $newPrimary->writeable()->save();

        $event = new PrimaryChanged($oldPrimary->uuid, $newPrimary->uuid);
        $projector = new ContactProjector();
        $projector->onPrimaryChanged($event);

        $oldPrimaryFresh = Contact::find($oldPrimary->uuid);
        $newPrimaryFresh = Contact::find($newPrimary->uuid);

        $this->assertFalse($oldPrimaryFresh->is_primary);
        $this->assertTrue($newPrimaryFresh->is_primary);
    }

    public function testOnPrimaryChangedThrowsException()
    {
        $user = User::factory()->create();
        // Create one contact and use a non-existent UUID for the new primary.
        $oldPrimary = Contact::factory()->make([
            'value'      => 'oldprimary@example.com',
            'is_primary' => true,
            'is_verified' => true,
            'contactable_type' => $user->getMorphClass(),
            'contactable_id' => $user->id,

        ]);
        $oldPrimary->writeable()->save();

        $nonExistentUuid = 'non-existent-uuid';

        $event = new PrimaryChanged($oldPrimary->uuid, $nonExistentUuid);
        $projector = new ContactProjector();

        $this->expectException(Exception::class);
        $projector->onPrimaryChanged($event);
    }
}
