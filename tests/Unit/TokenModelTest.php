<?php

namespace Kwidoo\Contacts\Tests\Unit;

use Kwidoo\Contacts\Models\Token;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Kwidoo\Contacts\Tests\TestCase;
use Illuminate\Support\Str;

class TokenModelTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        // Migrate the OTP table
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }


    public function testExpirationAndVerificationAttributes()
    {
        // Create a token with expires_at in the future and not verified.
        $token = Token::create([
            'uuid' => Str::uuid()->toString(),
            'token'       => '123456',
            'contact_uuid'  => 1,
            'value'       => 'test@example.com',
            'method'      => 'email',
            'expires_at'  => Carbon::now()->addMinutes(10),
            'verified_at' => null,
        ]);

        $this->assertFalse($token->is_expired);
        $this->assertFalse($token->is_verified);

        // Update token: expires_at in past and verified_at set.
        $token->update([
            'expires_at'  => Carbon::now()->subMinutes(10),
            'verified_at' => Carbon::now(),
        ]);

        $this->assertTrue($token->is_expired);
        $this->assertTrue($token->is_verified);
    }

    public function testQueryScopes()
    {
        // Create tokens with various states.
        Token::create([
            'uuid' => Str::uuid()->toString(),
            'token'       => '111111',
            'contact_uuid'  => 1,
            'value'       => 'test1@example.com',
            'method'      => 'email',
            'expires_at'  => Carbon::now()->addMinutes(10),
            'verified_at' => null,
        ]);

        Token::create([
            'uuid' => Str::uuid()->toString(),
            'token'       => '222222',
            'contact_uuid'  => 1,
            'value'       => 'test2@example.com',
            'method'      => 'email',
            'expires_at'  => Carbon::now()->subMinutes(10),
            'verified_at' => null,
        ]);

        Token::create([
            'uuid' => Str::uuid()->toString(),
            'token'       => '333333',
            'contact_uuid'  => 1,
            'value'       => 'test3@example.com',
            'method'      => 'email',
            'expires_at'  => Carbon::now()->addMinutes(10),
            'verified_at' => Carbon::now(),
        ]);

        // isNotExpired: tokens whose expires_at is in the future.
        $notExpired = Token::query()->isNotExpired()->get();
        $this->assertCount(2, $notExpired);

        // isNotVerified: tokens with null verified_at.
        $notVerified = Token::query()->isNotVerified()->get();
        $this->assertCount(2, $notVerified);
    }

    public function testDynamicTableAndKeyResolution()
    {
        // Override configuration for the test.
        config()->set('contacts.token.table', 'custom_tokens');
        config()->set('contacts.uuid', true);

        $token = new Token([
            'uuid' => Str::uuid()->toString(),
            'token'       => '123456',
            'contact_uuid'  => 1,
            'value'       => 'test@example.com',
            'method'      => 'email',
            'expires_at'  => Carbon::now()->addMinutes(10),
            'verified_at' => null,
        ]);

        $this->assertEquals('custom_tokens', $token->getTable());
        $this->assertEquals('uuid', $token->getRouteKeyName());
        $this->assertEquals('uuid', $token->getKeyName());
        $this->assertFalse($token->getIncrementing());
    }
}
