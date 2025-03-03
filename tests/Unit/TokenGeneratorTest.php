<?php

namespace Kwidoo\Contacts\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kwidoo\Contacts\Models\Contact;
use Kwidoo\Contacts\Models\Token;
use Kwidoo\Contacts\Contracts\TokenGenerator;
use Kwidoo\Contacts\Tests\Fixtures\User;
use Kwidoo\Contacts\Tests\TestCase;
use Illuminate\Support\Str;

class TokenGeneratorTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function generatesTokenWithCorrectAttributes()
    {
        $user = User::factory()->create();

        /** @var Contact */
        $contact = Contact::factory()->make(
            [
                'uuid' => Str::uuid()->toString(),
                'is_primary' => false,
                'type' => 'phone',
                'value' => '+1234567890',
                'contactable_type' => $user->getMorphClass(),
                'contactable_id' => $user->getKey()
            ]
        );
        $contact->writeable()->save();

        $generator = app()->make(TokenGenerator::class, ['contact' => $contact]);

        /** @var Token */
        $token = $generator->generate();

        $this->assertEquals($contact->id, $token->contact_id);
        $this->assertEquals($contact->type, $token->method);
        $this->assertEquals(
            now()->addMinutes(config('contacts.token.ttl'))->getTimestamp(),
            $token->expires_at->getTimestamp(),
            5 // Allow 5 second difference
        );
    }

    /** @test */
    public function tokenStringIsProperlyFormatted()
    {
        config(['contacts.token.length' => 6]);

        $user = User::factory()->create();

        /** @var Contact */
        $contact = Contact::factory()->make(
            [
                'uuid' => Str::uuid()->toString(),
                'is_primary' => false,
                'type' => 'phone',
                'value' => '+1234567890',
                'contactable_type' => $user->getMorphClass(),
                'contactable_id' => $user->getKey()
            ]
        );
        $contact->writeable()->save();
        $generator = app()->make(TokenGenerator::class, ['contact' => $contact]);

        /** @var Token */
        $token = $generator->generate();

        $this->assertMatchesRegularExpression('/^\d{6}$/', $token->token);
        $this->assertGreaterThanOrEqual(0, (int)$token->token);
        $this->assertLessThanOrEqual(999999, (int)$token->token);
    }
}
