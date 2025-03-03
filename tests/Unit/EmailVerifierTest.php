<?php

namespace Kwidoo\Contacts\Tests\Unit;

use Kwidoo\Contacts\Tests\Fixtures\User;
use Kwidoo\Contacts\Models\Contact;
use Kwidoo\Contacts\Models\Token;
use Kwidoo\Contacts\Services\EmailVerifier;
use Illuminate\Support\Facades\Notification;
use Kwidoo\Contacts\Contracts\TokenGenerator;
use Kwidoo\Contacts\Notifications\TokenNotification;
use Kwidoo\Contacts\Tests\TestCase;
use Mockery;

class EmailVerifierTest extends TestCase
{
    protected Contact $contact;
    protected $tokenGenerator;

    public function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->contact = Contact::factory()->make([
            'type' => 'email',
            'contactable_type' => $user->getMorphClass(),
            'contactable_id' => $user->id,
        ]);
        $this->contact->writeable()->save();
        $this->tokenGenerator = app()->make(TokenGenerator::class, ['contact' => $this->contact]);
    }

    /** @test */
    public function create_sends_notification_with_correct_token()
    {
        Notification::fake();

        $token = Token::factory()->create(['token' => '123456']);
        $this->tokenGenerator->shouldReceive('generate')->andReturn($token);

        $verifier = new EmailVerifier($this->tokenGenerator, $this->contact);
        $verifier->create();

        Notification::assertSentTo(
            $this->contact,
            TokenNotification::class,
            fn($notification) => $notification->token === '123456'
        );
    }

    /** @test */
    public function verify_token_successfully_updates_verified_at()
    {
        $token = Token::factory()->create([
            'contact_uuid' => $this->contact->uuid,
            'expires_at' => now()->addHour()
        ]);

        $verifier = new EmailVerifier($this->tokenGenerator, $this->contact);
        $result = $verifier->verify($token->token);

        $this->assertTrue($result);
        $this->assertNotNull($token->fresh()->verified_at);
    }

    /** @test */
    public function verify_returns_false_for_invalid_token()
    {
        $verifier = new EmailVerifier($this->tokenGenerator, $this->contact);

        $this->assertFalse($verifier->verify('invalid'));
    }
}
