<?php

namespace Kwidoo\Contacts\Tests\Unit;

use Illuminate\Support\Str;
use Kwidoo\Contacts\Exceptions\DuplicateContactException;
use Kwidoo\Contacts\Models\Contact;
use Kwidoo\Contacts\Tests\Fakes\FakeContactableModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kwidoo\Contacts\Tests\TestCase;

class ContactModelTest extends TestCase
{
    use RefreshDatabase;

    public function testAutomaticUuidGeneration()
    {
        // Enable UUID generation.
        config()->set('contacts.uuid', true);
        config()->set('contacts.table', 'contacts');

        // Create a contact record.
        $contact = Contact::create([
            'contactable_type' => 'FakeContactable',
            'contactable_id'   => 1,
            'type'             => 'email',
            'value'            => 'test@example.com',
            'is_primary'       => false,
            'is_verified'      => false,
        ]);

        $this->assertNotEmpty($contact->uuid);
        $this->assertTrue(Str::isUuid($contact->uuid));
    }

    public function testPrimaryStatusDeterminationAndDuplicateTriggering()
    {
        // Enable UUID generation.
        config()->set('contacts.uuid', true);
        config()->set('contacts.table', 'contacts');

        $fakeContactable = new FakeContactableModel();
        $fakeContactable->save();

        // Create the first contact – should be marked as primary.
        $contact1 = $fakeContactable->contacts()->create([
            'type'        => 'email',
            'value'       => 'primary@example.com',
            'is_verified' => false,
        ]);
        $this->assertTrue($contact1->is_primary);

        // Attempting to create a duplicate contact (same type and value)
        // should trigger the DuplicateContactException.
        $this->expectException(DuplicateContactException::class);

        $fakeContactable->contacts()->create([
            'type'        => 'email',
            'value'       => 'primary@example.com',
            'is_verified' => false,
        ]);
    }
}
