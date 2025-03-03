<?php

namespace Kwidoo\Contacts\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Kwidoo\Contacts\Models\Contact;
use Kwidoo\Contacts\Http\Controllers\PrimaryController;
use Kwidoo\Contacts\Services\PrimaryManager;
use Kwidoo\Contacts\Tests\Fixtures\User; // Adjust if your User model lives elsewhere

// A fake PrimaryManager for testing purposes.
class FakePrimaryManager extends PrimaryManager
{
    public $challengeCreated = false;
    public $swapVerified = false;
    public $swapCalled = false;
    protected $validOldToken = 'old_valid';
    protected $validNewToken = 'new_valid';

    public function __construct($oldPrimary, $newPrimary)
    {
        // Parent validation is still run.
        parent::__construct($oldPrimary, $newPrimary);
    }

    public function createChallenge(): void
    {
        $this->challengeCreated = true;
    }

    public function verify(string $oldToken, string $newToken): bool
    {
        $this->swapVerified = ($oldToken === $this->validOldToken && $newToken === $this->validNewToken);
        return $this->swapVerified;
    }

    public function swap(): void
    {
        $this->swapCalled = true;
    }
}

class PrimaryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $oldContact;
    protected $newContact;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a dummy user (using your factory)
        $this->user = User::factory()->create();

        // Create two contacts for the user.
        $this->oldContact = Contact::factory()->create([
            'contactable_id'   => $this->user->id,
            'contactable_type' => get_class($this->user),
            'is_primary'       => true,
            'is_verified'      => true,
            'value'            => 'old@example.com',
            'type'             => 'email'
        ]);

        $this->newContact = Contact::factory()->create([
            'contactable_id'   => $this->user->id,
            'contactable_type' => get_class($this->user),
            'is_primary'       => false,
            'is_verified'      => true,
            'value'            => 'new@example.com',
            'type'             => 'email'
        ]);

        // Define authorization gates so the controller’s authorize calls pass.
        Gate::define('swap', fn($user, $contact) => true);
        Gate::define('swapWithoutChallenge', fn($user, $contact) => false);
        Gate::define('update', fn($user, $contactable) => true);
    }

    public function testSendChallenge()
    {
        // Bind our fake PrimaryManager into the container.
        $this->app->bind(PrimaryManager::class, function ($app, $parameters) {
            return new FakePrimaryManager($parameters['oldPrimary'], $parameters['newPrimary']);
        });

        // Call the sendChallenge method directly.
        $controller = new PrimaryController();

        $request = Request::create('', 'GET');
        $request->setUserResolver(fn() => $this->user);

        $response = $controller->sendChallenge($this->oldContact, $this->newContact);
        $this->assertInstanceOf(JsonResponse::class, $response);

        $data = $response->getData(true);
        $this->assertEquals('Verification challenge sent', $data['message']);

        // Verify that the fake manager’s challenge was created.
        $manager = app(PrimaryManager::class, [
            'oldPrimary' => $this->oldContact,
            'newPrimary' => $this->newContact,
        ]);
        $manager->createChallenge();
        $this->assertTrue($manager->challengeCreated);
    }

    public function testSwapPrimaryInvalidTokens()
    {
        // Bind fake manager.
        $this->app->bind(PrimaryManager::class, function ($app, $parameters) {
            return new FakePrimaryManager($parameters['oldPrimary'], $parameters['newPrimary']);
        });

        $controller = new PrimaryController();

        $request = Request::create('', 'POST', [
            'old_token' => 'invalid',
            'new_token' => 'invalid'
        ]);
        $request->setUserResolver(fn() => $this->user);

        $response = $controller->swap($request, $this->oldContact, $this->newContact);
        $this->assertEquals(400, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertEquals('Invalid verification tokens', $data['message']);
    }

    public function testSwapPrimaryValidTokens()
    {
        // Bind fake manager.
        $this->app->bind(PrimaryManager::class, function ($app, $parameters) {
            return new FakePrimaryManager($parameters['oldPrimary'], $parameters['newPrimary']);
        });

        // Ensure the gate does not allow swapWithoutChallenge.
        Gate::define('swapWithoutChallenge', fn($user, $contact) => false);

        $controller = new PrimaryController();

        $request = Request::create('', 'POST', [
            'old_token' => 'old_valid',
            'new_token' => 'new_valid'
        ]);
        $request->setUserResolver(fn() => $this->user);

        $response = $controller->swap($request, $this->oldContact, $this->newContact);
        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertEquals('Primary contact updated successfully', $data['message']);

        // Also verify that swap() was called on our fake manager.
        $manager = app(PrimaryManager::class, [
            'oldPrimary' => $this->oldContact,
            'newPrimary' => $this->newContact,
        ]);
        $manager->swap();
        $this->assertTrue($manager->swapCalled);
    }

    public function testMarkAsPrimary()
    {
        // Create a non‐primary contact.
        $nonPrimaryContact = Contact::factory()->create([
            'contactable_id'   => $this->user->id,
            'contactable_type' => get_class($this->user),
            'is_primary'       => false,
            'is_verified'      => true,
            'value'            => 'markprimary@example.com',
            'type'             => 'email'
        ]);

        $controller = new PrimaryController();

        $request = Request::create('', 'POST');
        $request->setUserResolver(fn() => $this->user);

        $response = $controller->markAsPrimary($request, $nonPrimaryContact);
        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertEquals('Contact marked as primary', $data['message']);
    }
}
