<?php

namespace Kwidoo\Contacts\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kwidoo\Contacts\ContactServiceProvider;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use MockeryPHPUnitIntegration;
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->app['db']->connection()->getSchemaBuilder()->create('users', function ($table) {
            $table->uuid('uuid')->primary();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function getPackageProviders($app)
    {
        return [
            ContactServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('contacts.verifiers', [
            'email' => \Kwidoo\Contacts\Services\EmailVerifier::class,
            'phone' => \Kwidoo\Contacts\Services\PhoneVerifier::class,
        ]);
        $app['config']->set('auth.guards.api', [
            'driver' => 'token', // or 'session' depending on your needs
            'provider' => 'users',
        ]);

        // Define the 'users' provider.
        $app['config']->set('auth.providers.users', [
            'driver' => 'eloquent',
            'model' => \Kwidoo\Contacts\Tests\Fixtures\User::class,
        ]);
    }
}
