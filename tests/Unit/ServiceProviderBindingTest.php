<?php

namespace Kwidoo\Contacts\Tests\Unit;

use InvalidArgumentException;
use stdClass;
use Kwidoo\Contacts\Contracts\ContactService as ContactServiceContract;
use Kwidoo\Contacts\Contracts\TokenGenerator as TokenGeneratorContract;
use Kwidoo\Contacts\Contracts\VerificationService as VerificationServiceContract;
use Kwidoo\Contacts\Services\ContactService;
use Kwidoo\Contacts\Services\TokenGenerator;
use Kwidoo\Contacts\Services\VerificationService;
use Kwidoo\Contacts\Tests\TestCase;

class ServiceProviderBindingTest extends TestCase
{


    /** @test */
    public function testRegistersTheContactServiceBindingCorrectly()
    {
        $service = $this->app->make(ContactServiceContract::class);
        $this->assertInstanceOf(ContactService::class, $service);
    }

    /** @test */
    public function testRegistersTheTokenGeneratorBindingCorrectly()
    {
        // For the token generator, we need to pass the 'contact' parameter.
        $contact = $this->getValidContact();
        $tokenGenerator = $this->app->make(TokenGeneratorContract::class, ['contact' => $contact]);
        $this->assertInstanceOf(TokenGenerator::class, $tokenGenerator);
    }

    /** @test */
    public function registersTheVerificationServiceBindingCorrectlyWithValidContactTest()
    {
        $contact = $this->getValidContact();
        $verificationService = $this->app->make(VerificationServiceContract::class, ['contact' => $contact]);
        $this->assertInstanceOf(VerificationService::class, $verificationService);
    }

    /** @test */
    public function testThrowsExceptionWhenVerificationServiceBindingReceivesInvalidContact()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A valid Contact instance is required.');

        // Pass an invalid contact (not implementing ContactContract)
        $this->app->make(VerificationServiceContract::class, ['contact' => new stdClass()]);
    }
}
