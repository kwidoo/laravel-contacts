<?php

namespace Kwidoo\Contacts\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kwidoo\Contacts\Tests\Fixtures\User;
use Kwidoo\Contacts\Models\Contact;
use Kwidoo\Contacts\Services\PhoneVerifier;
use Kwidoo\Contacts\Contracts\TokenGenerator;
use Kwidoo\Contacts\Tests\TestCase;
use Kwidoo\MultiAuth\Services\TwilioService;
use Mockery;

class PhoneVerifierTest extends TestCase
{
    use RefreshDatabase;

    protected Contact $contact;
    protected $twilio;
    protected $tokenGenerator;

    public function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->contact = Contact::factory()->make([
            'type' => 'phone',
            'contactable_type' => $user->getMorphClass(),
            'contactable_id' => $user->id,
            'value' => '+1234567890'
        ]);
        $this->contact->writeable()->save();
        $this->twilio = Mockery::mock(TwilioService::class);
        $this->tokenGenerator = app()->make(TokenGenerator::class, ['contact' => $this->contact]);
    }

    /** @test */
    public function testCreateTriggersTwilioService()
    {
        $this->twilio->shouldReceive('create')->with('+1234567890')->once();

        $verifier = new PhoneVerifier($this->contact, $this->tokenGenerator, $this->twilio);
        $verifier->create();
    }

    /** @test */
    public function testVerifyReturnsTrueForValidOtp()
    {
        $this->twilio->shouldReceive('validate')
            ->with(['+1234567890', '123456'])
            ->andReturn(true);

        $verifier = new PhoneVerifier($this->contact, $this->tokenGenerator, $this->twilio);
        $this->assertTrue($verifier->verify('123456'));
    }

    /** @test */
    public function testVerifyReturnsFalseForInvalidOtp()
    {
        $this->twilio->shouldReceive('validate')
            ->with(['+1234567890', 'wrong'])
            ->andReturn(false);

        $verifier = new PhoneVerifier($this->contact, $this->tokenGenerator, $this->twilio);
        $this->assertFalse($verifier->verify('wrong'));
    }
}
