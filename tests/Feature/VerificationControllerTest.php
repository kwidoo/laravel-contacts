<?php

namespace Kwidoo\Contacts\Tests\Feature;

use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kwidoo\Contacts\Contracts\VerificationService;
use Kwidoo\Contacts\Models\Contact;
use Kwidoo\Contacts\Tests\TestCase;
use Mockery;

class VerificationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app)
    {
        return [
            \Kwidoo\Contacts\ContactServiceProvider::class,
        ];
    }

    public function testSendVerificationCallsCreateAndReturnsSuccessMessage()
    {
        $contact = Contact::factory()->make();
        $contact->writeable()->save();

        $verificationServiceMock = Mockery::mock(VerificationService::class);
        $verificationServiceMock->shouldReceive('create')->once();

        $this->app->bind(VerificationService::class, function ($app, $params) use ($contact, $verificationServiceMock) {
            if ($params['contact'] === $contact) {
                return $verificationServiceMock;
            }
            throw new Exception("Unexpected contact");
        });

        $response = $this->postJson("/contacts/verify/{$contact->getKey()}");
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Verification sent']);
    }

    public function testVerifyTokenReturnsSuccessForValidToken()
    {
        $contact = Contact::factory()->make();
        $contact->writeable()->save();

        $verificationServiceMock = Mockery::mock(VerificationService::class);
        $verificationServiceMock->shouldReceive('verify')
            ->with('valid-token')
            ->once()
            ->andReturn(true);

        $this->app->bind(VerificationService::class, function ($app, $params) use ($contact, $verificationServiceMock) {
            if ($params['contact'] === $contact) {
                return $verificationServiceMock;
            }
            throw new Exception("Unexpected contact");
        });

        $response = $this->getJson("/contacts/verify/{$contact->getKey()}/valid-token");
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Contact verified']);
    }

    public function testVerifyTokenReturnsErrorForInvalidToken()
    {
        $contact = Contact::factory()->make();
        $contact->writeable()->save();

        $verificationServiceMock = Mockery::mock(VerificationService::class);
        $verificationServiceMock->shouldReceive('verify')
            ->with('invalid-token')
            ->once()
            ->andReturn(false);

        $this->app->bind(VerificationService::class, function ($app, $params) use ($contact, $verificationServiceMock) {
            if ($params['contact'] === $contact) {
                return $verificationServiceMock;
            }
            throw new Exception("Unexpected contact");
        });

        $response = $this->getJson("/contacts/verify/{$contact->getKey()}/invalid-token");
        $response->assertStatus(400);
        $response->assertJson(['message' => 'Invalid token']);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
