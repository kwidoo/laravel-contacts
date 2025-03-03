<?php

namespace Kwidoo\Contacts\Tests\Feature;

use Kwidoo\Contacts\Tests\Fixtures\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Kwidoo\Contacts\Exceptions\ContactServiceException;
use Kwidoo\Contacts\Exceptions\DuplicateContactException;
use Kwidoo\Contacts\Models\Contact;
use Kwidoo\Contacts\Services\ContactService;
use Kwidoo\Contacts\Contracts\Contactable;
use Kwidoo\Contacts\Tests\TestCase;
use Kwidoo\Contacts\Traits\HasContacts;

// A simple dummy model to act as the "contactable" entity.
class DummyUser extends \Illuminate\Database\Eloquent\Model implements Contactable
{
    use HasContacts;

    protected $table = 'users';
    protected $guarded = [];
}

class ContactServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_create_contact_returns_valid_uuid()
    {
        $service = new ContactService($this->user);
        $uuid = $service->create('email', 'new@example.com');

        $this->assertTrue(Str::isUuid($uuid), 'Returned UUID should be valid.');

        // Check that the contact exists in the database.
        $contact = \DB::table(config('contacts.table'))->where('uuid', $uuid)->first();
        $this->assertNotNull($contact, 'Contact should be persisted in the database.');
    }

    public function test_create_contact_with_invalid_type_throws_exception()
    {
        $this->expectException(ContactServiceException::class);

        $service = new ContactService($this->user);
        $service->create('fax', 'fax@example.com');
    }

    public function test_destroy_non_primary_contact()
    {
        $service = new ContactService($this->user);
        // The first contact created becomes primary.
        $uuidPrimary = $service->create('email', 'primary@example.com');
        // Creating a second contact makes it non-primary.
        $uuidSecondary = $service->create('phone', '+1234567890');

        $contact = Contact::where('uuid', $uuidSecondary)->first();
        $this->assertFalse($contact->is_primary, 'Second contact should be non-primary.');

        $result = $service->destroy($contact);
        $this->assertTrue($result, 'Non-primary contact should be soft-deleted.');

        $deletedContact = Contact::withTrashed()->where('uuid', $uuidSecondary)->first();
        $this->assertNotNull($deletedContact->deleted_at, 'Contact should be soft-deleted.');
    }

    public function test_destroy_primary_contact_throws_exception()
    {
        $this->expectException(ContactServiceException::class);

        $service = new ContactService($this->user);
        $uuid = $service->create('email', 'primary@example.com');

        $contact = Contact::where('uuid', $uuid)->first();
        $this->assertTrue($contact->is_primary, 'Contact should be primary.');

        $service->destroy($contact);
    }

    public function test_restore_soft_deleted_contact()
    {
        $service = new ContactService($this->user);
        $uuid = $service->create('phone', '+1234567890');

        $contact = Contact::where('uuid', $uuid)->first();

        // If the contact is primary (e.g. if it is the first contact), adjust its primary flag.
        if ($contact->is_primary) {
            $contact->update(['is_primary' => false]);
        }

        // Soft-delete the contact.
        $service->destroy($contact);

        $deletedContact = Contact::withTrashed()->where('uuid', $uuid)->first();
        $this->assertNotNull($deletedContact->deleted_at, 'Contact should be soft-deleted.');

        // Restore the contact.
        $restored = $service->restore($uuid);
        $this->assertTrue($restored, 'Contact should be restored.');

        $restoredContact = Contact::where('uuid', $uuid)->first();
        $this->assertFalse($restoredContact->is_primary, 'Restored contact should not be primary.');
    }

    public function test_duplicate_contact_exception_thrown()
    {
        $this->expectException(DuplicateContactException::class);

        $service = new ContactService($this->user);
        // Create the initial contact.
        $service->create('email', 'duplicate@example.com');
        // Attempt to create a duplicate contact.
        $service->create('email', 'duplicate@example.com');
    }
}
