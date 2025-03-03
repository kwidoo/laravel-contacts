<?php

namespace Kwidoo\Contacts\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kwidoo\Contacts\Tests\Fixtures\User;
use Kwidoo\Contacts\Models\Contact;
use Kwidoo\Contacts\Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a new contact is created and marked as primary if it is the first contact.
     */
    public function testStoreContactCreatesContactAndSetsPrimaryIfFirstContact()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');

        // Use the first allowed type from config
        $allowedTypes = config('contacts.types');
        $type = $allowedTypes[0] ?? 'email';

        $response = $this->postJson(route('contacts.store'), [
            'type'  => $type,
            'value' => 'test@example.com',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'value'      => 'test@example.com',
                'is_primary' => true,
                'is_verified' => false,
            ]);

        $this->assertDatabaseHas('contacts', [
            'contactable_id'   => $user->id,
            'contactable_type' => get_class($user),
            'value'            => 'test@example.com',
            'is_primary'       => true,
        ]);
    }

    /**
     * Test that a non-admin user cannot update a contact they do not own.
     */
    public function testNonAdminCannotUpdateAnotherUsersContact()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $otherUser = User::factory()->create(['is_admin' => false]);
        $contact = Contact::factory()->create([
            'contactable_id'   => $otherUser->id,
            'contactable_type' => get_class($otherUser),
            'value'            => 'old@example.com',
        ]);

        $this->actingAs($user, 'api');
        $response = $this->putJson(route('contacts.update', $contact->uuid), [
            'value' => 'new@example.com',
        ]);

        // Expecting a permission exception (403 Forbidden)
        $response->assertStatus(403);
    }

    /**
     * Test that a non-admin user can update their own contact.
     */
    public function testNonAdminCanUpdateTheirOwnContact()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $contact = Contact::factory()->create([
            'contactable_id'   => $user->id,
            'contactable_type' => get_class($user),
            'value'            => 'old@example.com',
        ]);

        $this->actingAs($user, 'api');
        $response = $this->putJson(route('contacts.update', $contact->uuid), [
            'value' => 'new@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'updated' => true,
            ]);
        $this->assertDatabaseHas('contacts', [
            'uuid'  => $contact->uuid,
            'value' => 'new@example.com',
        ]);
    }

    /**
     * Test that an admin user can update any contact.
     */
    public function testAdminCanUpdateAnyContact()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $otherUser = User::factory()->create(['is_admin' => false]);
        $contact = Contact::factory()->create([
            'contactable_id'   => $otherUser->id,
            'contactable_type' => get_class($otherUser),
            'value'            => 'old@example.com',
        ]);

        $this->actingAs($admin, 'api');
        $response = $this->putJson(route('contacts.update', $contact->uuid), [
            'value' => 'admin_updated@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'updated' => true,
            ]);
        $this->assertDatabaseHas('contacts', [
            'uuid'  => $contact->uuid,
            'value' => 'admin_updated@example.com',
        ]);
    }

    /**
     * Test that a non-admin user cannot delete a contact they do not own.
     */
    public function testNonAdminCannotDeleteOtherUsersContact()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $otherUser = User::factory()->create(['is_admin' => false]);
        $contact = Contact::factory()->create([
            'contactable_id'   => $otherUser->id,
            'contactable_type' => get_class($otherUser),
        ]);

        $this->actingAs($user, 'api');
        $response = $this->deleteJson(route('contacts.destroy', $contact->uuid));

        $response->assertStatus(403);
    }

    /**
     * Test that deleting a primary contact is not allowed.
     */
    public function testCannotDeletePrimaryContact()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $contact = Contact::factory()->create([
            'contactable_id'   => $user->id,
            'contactable_type' => get_class($user),
            'is_primary'       => true,
        ]);

        $this->actingAs($user, 'api');
        $response = $this->deleteJson(route('contacts.destroy', $contact->uuid));

        // Depending on exception handling, this may return a 422 or 403 status code.
        $response->assertStatus(422);
    }

    /**
     * Test that a non-primary contact can be deleted successfully.
     */
    public function testDeleteContactSuccessfully()
    {
        $user = User::factory()->create(['is_admin' => false]);

        // Create two contacts so that one is primary and one is secondary.
        $primary = Contact::factory()->create([
            'contactable_id'   => $user->id,
            'contactable_type' => get_class($user),
            'is_primary'       => true,
        ]);
        $secondary = Contact::factory()->create([
            'contactable_id'   => $user->id,
            'contactable_type' => get_class($user),
            'is_primary'       => false,
        ]);

        $this->actingAs($user, 'api');
        $response = $this->deleteJson(route('contacts.destroy', $secondary->uuid));

        $response->assertStatus(200)
            ->assertJsonFragment(['deleted' => true]);
        $this->assertSoftDeleted('contacts', ['uuid' => $secondary->uuid]);
    }

    /**
     * Test that verifying a contact with the correct token works.
     */
    public function testVerifyContactWithCorrectToken()
    {
        $user = User::factory()->create();
        $token = 'correct-token';
        $contact = Contact::factory()->create([
            'contactable_id'      => $user->id,
            'contactable_type'    => get_class($user),
            'verification_token'  => $token,
            'is_verified'         => false,
        ]);

        $this->actingAs($user, 'api');
        $response = $this->postJson(route('contacts.verify', $contact->uuid), [
            'verification_token' => $token,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['verified' => true]);
        $this->assertDatabaseHas('contacts', [
            'uuid'               => $contact->uuid,
            'is_verified'        => true,
            'verification_token' => null,
        ]);
    }

    /**
     * Test that verifying a contact with an incorrect token fails.
     */
    public function testVerifyContactWithIncorrectToken()
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create([
            'contactable_id'      => $user->id,
            'contactable_type'    => get_class($user),
            'verification_token'  => 'correct-token',
            'is_verified'         => false,
        ]);

        $this->actingAs($user, 'api');
        $response = $this->postJson(route('contacts.verify', $contact->uuid), [
            'verification_token' => 'wrong-token',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['verified' => false]);
        $this->assertDatabaseHas('contacts', [
            'uuid'        => $contact->uuid,
            'is_verified' => false,
        ]);
    }

    /**
     * Test that marking a contact as primary fails if the contact is not verified.
     */
    public function testMarkContactAsPrimaryFailsIfNotVerified()
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create([
            'contactable_id'      => $user->id,
            'contactable_type'    => get_class($user),
            'is_verified'         => false,
        ]);

        $this->actingAs($user, 'api');
        $response = $this->postJson(route('contacts.markAsPrimary', $contact->uuid));

        // Expecting an error due to unverified status.
        $response->assertStatus(422);
    }

    /**
     * Test that a verified contact can be marked as primary and that any previously primary contact is unset.
     */
    public function testMarkContactAsPrimarySuccessfully()
    {
        $user = User::factory()->create();
        // Create two verified contacts.
        $contactOne = Contact::factory()->create([
            'contactable_id'      => $user->id,
            'contactable_type'    => get_class($user),
            'is_verified'         => true,
            'is_primary'          => false,
        ]);
        $contactTwo = Contact::factory()->create([
            'contactable_id'      => $user->id,
            'contactable_type'    => get_class($user),
            'is_verified'         => true,
            'is_primary'          => true,
        ]);

        $this->actingAs($user, 'api');
        // Mark the non-primary contact as primary.
        $response = $this->postJson(route('contacts.markAsPrimary', $contactOne->uuid));

        $response->assertStatus(200)
            ->assertJsonFragment(['marked_as_primary' => true]);

        // Verify that contactOne is now primary.
        $this->assertDatabaseHas('contacts', [
            'uuid'       => $contactOne->uuid,
            'is_primary' => true,
        ]);
        // Verify that contactTwo is no longer primary.
        $this->assertDatabaseHas('contacts', [
            'uuid'       => $contactTwo->uuid,
            'is_primary' => false,
        ]);
    }

    /**
     * Test that a soft-deleted contact can be restored.
     */
    public function testRestoreSoftDeletedContact()
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create([
            'contactable_id'   => $user->id,
            'contactable_type' => get_class($user),
        ]);

        // Soft-delete the contact.
        $contact->delete();
        $this->assertSoftDeleted('contacts', ['uuid' => $contact->uuid]);

        // Restore the contact via the service.
        $contactService = app(\Kwidoo\Contacts\Contracts\ContactService::class);
        $contactService->setContactable($user);
        $restored = $contactService->restore($contact->uuid);

        $this->assertTrue($restored);

        // Check that the contact is restored (i.e. not soft-deleted).
        $this->assertDatabaseHas('contacts', [
            'uuid'       => $contact->uuid,
            'deleted_at' => null,
        ]);
    }
}
