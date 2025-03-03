<?php

namespace Kwidoo\Contacts\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kwidoo\Contacts\Aggregates\ContactAggregateRoot;
use Kwidoo\Contacts\Contracts\Verifier;
use Kwidoo\Contacts\Models\Contact;
use Kwidoo\Contacts\Services\VerificationService;
use Kwidoo\Contacts\Tests\Fixtures\User;
use Kwidoo\Contacts\Tests\TestCase;
use Mockery;

class VerificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Contact $contact;
    protected $verifier;
    protected $aggregateRoot;

    public function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();

        /** @var Contact */
        $this->contact = Contact::factory()->make(
            [
                'is_primary' => false,
                'type' => 'phone',
                'value' => '+1234567890',
                'contactable_type' => $user->getMorphClass(),
                'contactable_id' => $user->getKey()
            ]
        );
        $this->contact->writeable()->save();
        $this->verifier = Mockery::mock(Verifier::class);
        $this->aggregateRoot = Mockery::mock(ContactAggregateRoot::class);
    }

    /** @test */
    public function create_triggers_event_and_verifier_create()
    {
        ContactAggregateRoot::fake();

        $this->verifier->shouldReceive('create')->once();
        $this->aggregateRoot->shouldReceive('startVerification')->once()->andReturnSelf();
        $this->aggregateRoot->shouldReceive('persist')->once();

        ContactAggregateRoot::shouldReceive('retrieve')
            ->with($this->contact->getKey())
            ->andReturn($this->aggregateRoot);

        $service = new VerificationService($this->verifier, $this->contact);
        $service->create();
    }

    /** @test */
    public function verify_success_updates_contact_status()
    {
        ContactAggregateRoot::fake();

        $this->verifier->shouldReceive('verify')->with('valid')->andReturn(true);
        $this->aggregateRoot->shouldReceive('verifyContact')->once()->andReturnSelf();
        $this->aggregateRoot->shouldReceive('persist')->once();

        ContactAggregateRoot::shouldReceive('retrieve')
            ->with($this->contact->getKey())
            ->andReturn($this->aggregateRoot);

        $service = new VerificationService($this->verifier, $this->contact);
        $this->assertTrue($service->verify('valid'));
    }

    /** @test */
    public function verify_failure_returns_false()
    {
        $this->verifier->shouldReceive('verify')->with('invalid')->andReturn(false);

        $service = new VerificationService($this->verifier, $this->contact);
        $this->assertFalse($service->verify('invalid'));
    }
}
