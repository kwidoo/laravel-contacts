<?php

namespace Kwidoo\Contacts\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Kwidoo\Contacts\ContactServiceProvider;
use Kwidoo\Contacts\Tests\TestCase;

class ServiceProviderBootstrappingTest extends TestCase
{
    use RefreshDatabase;
    /** @test */
    public function testLoadsMigrationsFromThePackage()
    {
        $this->assertTrue(Schema::hasTable('contacts'), 'The contacts table does not exist. Migrations were not loaded.');
    }

    /** @test */
    public function testMergesAndPublishesConfigurationCorrectly()
    {
        // mergeConfigFrom should have merged the contacts config
        $config = config('contacts');
        $this->assertIsArray($config);
        $this->assertArrayHasKey('table', $config);

        // Publishing: since publishing is only available via artisan,
        // we can simulate that the publish configuration key exists.
        $publishes = $this->app->getProvider(ContactServiceProvider::class)->publishes;
        $this->assertNotEmpty($publishes, 'Configuration publishing not set.');
    }

    /** @test */
    public function testLoadsPackageRoutes()
    {
        // The provider loads routes (e.g. 'contacts.verify').
        $route = Route::has('contacts.verify');
        $this->assertTrue($route, 'Route contacts.verify is not defined.');
    }
}
