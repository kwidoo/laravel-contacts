<?php

namespace Kwidoo\Contacts\Tests\Unit;

use Kwidoo\Contacts\Tests\Fixtures\User;
use Exception;
use Kwidoo\Contacts\Services\PrimaryManager;
use Kwidoo\Contacts\Contracts\VerificationService;
use Kwidoo\Contacts\Aggregates\ContactAggregateRoot;
use Kwidoo\Contacts\Events\PrimaryChanged;
use InvalidArgumentException;
use Kwidoo\Contacts\Models\Contact;
use Kwidoo\Contacts\Tests\TestCase;
use Mockery;


class PrimaryManagerTest extends TestCase
{


    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCreateChallengeCallsVerificationCreateForBothContacts()
    {
        // Create dummy contacts – the old contact must be primary and the new contact verified.
        $user = User::factory()->create();

        $oldContact = new Contact('old-uuid', true, true, $user);
        // Note: new contact need not be primary; only it must be verified.
        $newContact = new Contact('new-uuid', false, true, $user);

        // Create mocks for VerificationService for old and new contacts.
        $oldVerificationMock = Mockery::mock(VerificationService::class);
        $oldVerificationMock->shouldReceive('create')->once();

        $newVerificationMock = Mockery::mock(VerificationService::class);
        $newVerificationMock->shouldReceive('create')->once();

        // Bind the VerificationService resolution so that when the PrimaryManager is constructed,
        // the appropriate mock is returned based on the contact.
        $this->app->bind(VerificationService::class, function ($app, $params) use ($oldContact, $newContact, $oldVerificationMock, $newVerificationMock) {
            if ($params['contact'] === $oldContact) {
                return $oldVerificationMock;
            }
            if ($params['contact'] === $newContact) {
                return $newVerificationMock;
            }
            throw new Exception("Unexpected contact");
        });

        $primaryManager = new PrimaryManager($oldContact, $newContact);

        // Calling createChallenge() should call create() on both verification services.
        $primaryManager->createChallenge();
    }

    public function testVerifyReturnsTrueWhenBothTokensVerified()
    {
        // Create dummy contacts – the old contact must be primary and the new contact verified.
        $user = User::factory()->create();

        $oldContact = new Contact('old-uuid', true, true, $user);
        // Note: new contact need not be primary; only it must be verified.
        $newContact = new Contact('new-uuid', false, true, $user);

        $oldVerificationMock = Mockery::mock(VerificationService::class);
        $oldVerificationMock->shouldReceive('verify')->with('old-token')->once()->andReturn(true);

        $newVerificationMock = Mockery::mock(VerificationService::class);
        $newVerificationMock->shouldReceive('verify')->with('new-token')->once()->andReturn(true);

        $this->app->bind(VerificationService::class, function ($app, $params) use ($oldContact, $newContact, $oldVerificationMock, $newVerificationMock) {
            if ($params['contact'] === $oldContact) {
                return $oldVerificationMock;
            }
            if ($params['contact'] === $newContact) {
                return $newVerificationMock;
            }
            throw new Exception("Unexpected contact");
        });

        $primaryManager = new PrimaryManager($oldContact, $newContact);

        $result = $primaryManager->verify('old-token', 'new-token');
        $this->assertTrue($result, 'Both tokens should be verified successfully.');
    }

    public function testVerifyReturnsFalseWhenOneTokenFails()
    {
        // Create dummy contacts – the old contact must be primary and the new contact verified.
        $user = User::factory()->create();

        $oldContact = new Contact('old-uuid', true, true, $user);
        // Note: new contact need not be primary; only it must be verified.
        $newContact = new Contact('new-uuid', false, true, $user);

        $oldVerificationMock = Mockery::mock(VerificationService::class);
        $oldVerificationMock->shouldReceive('verify')->with('old-token')->once()->andReturn(false);

        $newVerificationMock = Mockery::mock(VerificationService::class);
        $newVerificationMock->shouldReceive('verify')->with('new-token')->once()->andReturn(true);

        $this->app->bind(VerificationService::class, function ($app, $params) use ($oldContact, $newContact, $oldVerificationMock, $newVerificationMock) {
            if ($params['contact'] === $oldContact) {
                return $oldVerificationMock;
            }
            if ($params['contact'] === $newContact) {
                return $newVerificationMock;
            }
            throw new Exception("Unexpected contact");
        });

        $primaryManager = new PrimaryManager($oldContact, $newContact);

        $result = $primaryManager->verify('old-token', 'new-token');
        $this->assertFalse($result, 'Verification should fail if one token fails.');
    }

    public function testSwapTriggersPrimaryChange()
    {
        // Create dummy contacts – the old contact must be primary and the new contact verified.
        $user = User::factory()->create();

        $oldContact = new Contact('old-uuid', true, true, $user);
        // Note: new contact need not be primary; only it must be verified.
        $newContact = new Contact('new-uuid', false, true, $user);

        // No need to bind VerificationService here since swap() doesn't use it.
        $primaryManager = new PrimaryManager($oldContact, $newContact);

        // For demonstration, simply call swap() and assert that no exception is thrown.
        try {
            $primaryManager->swap();
            $this->assertTrue(true, 'Swap method executed without exceptions.');
        } catch (\Exception $e) {
            $this->fail('Swap method threw an exception: ' . $e->getMessage());
        }

        // In a real scenario you might do something like:
        ContactAggregateRoot::fake();
        $primaryManager->swap();
        ContactAggregateRoot::assertAggregateRootRecorded(PrimaryChanged::class, function ($event) use ($oldContact, $newContact) {
            return $event->oldContactUuid === $oldContact->getKey() &&
                $event->newContactUuid === $newContact->getKey();
        });
    }

    public function testValidationLogicThrowsExceptionWhenOldContactNotPrimary()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Old primary contact is not primary');

        // Create dummy contacts – the old contact must be primary and the new contact verified.
        $user = User::factory()->create();

        $oldContact = new Contact('old-uuid', true, true, $user);
        // Note: new contact need not be primary; only it must be verified.
        $newContact = new Contact('new-uuid', false, true, $user);

        new PrimaryManager($oldContact, $newContact);
    }

    public function testValidationLogicThrowsExceptionWhenNewContactNotVerified()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('New primary contact is not verified');

        $user = User::factory()->create();

        $oldContact = new DummyContact('old-uuid', true, true, $user);
        // New contact is not verified.
        $newContact = new DummyContact('new-uuid', false, false, $user);

        new PrimaryManager($oldContact, $newContact);
    }

    public function testValidationLogicThrowsExceptionWhenContactsNotBelongingToSameOwner()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('New primary contact does not belong to the same contactable');


        $users = User::factory()->count(2)->create();

        $oldContact = new Contact('old-uuid', true, true, $users[0]);
        // Note: new contact need not be primary; only it must be verified.
        $newContact = new Contact('new-uuid', false, true, $users[1]);

        new PrimaryManager($oldContact, $newContact);
    }
}
