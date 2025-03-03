<?php

namespace Kwidoo\Contacts\Tests\Feature;

use Kwidoo\Contacts\Tests\Fixtures\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kwidoo\Contacts\Models\Contact;
use Kwidoo\Contacts\Contracts\ContactService as ContactServiceContract;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Gate;
use Mockery;
use Illuminate\Support\Str;
use Kwidoo\Contacts\Tests\TestCase;

class ContactControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function testIndexReturnsPaginatedContactsForAuthorizedUser()
    {
        Contact::factory()->count(15)->make()->each(function ($contact) {
            $contact->writeable()->save();
            $contact->contactable()->associate(User::factory()->create());
        });

        /** @var \Illuminate\Foundation\Auth\User */
        $user = User::factory()->create();
        $user->allowedAbilities = ['viewAny'];

        $this->actingAs($user, 'api');

        $response = $this->getJson('/contacts');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'links',
            'meta',
        ]);
    }

    public function testIndexReturns403ForUnauthorizedUser()
    {
        /** @var \Illuminate\Foundation\Auth\User */
        $user = User::factory()->create();
        $user->allowedAbilities = []; // no permissions

        $this->actingAs($user, 'api');

        $response = $this->getJson('/contacts');
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Unauthorized to view contacts.'
        ]);
    }

    public function testShowReturnsContactForAuthorizedUser()
    {
        /** @var \Illuminate\Foundation\Auth\User */
        $user = User::factory()->create();
        $contact = Contact::factory()->make([
            'contactable_type' => $user->getMorphClass(),
            'contactable_id' => $user->getKey()
        ]);
        $contact->writeable()->save();
        $user->allowedAbilities = ['view'];

        // Simulate that the user is authorized to view the contact's contactable.
        Gate::shouldReceive('allows')->with('view', $contact->contactable)->andReturn(true);

        $this->actingAs($user, 'api');
        $response = $this->getJson("/contacts/{$contact->getKey()}");
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'type'  => $contact->type,
            'value' => $contact->value,
        ]);
    }

    public function testShowReturns403ForUnauthorizedUser()
    {
        /** @var \Illuminate\Foundation\Auth\User */
        $user = User::factory()->create();
        $contact = Contact::factory()->make([
            'contactable_type' => $user->getMorphClass(),
            'contactable_id' => $user->getKey()
        ]);
        $contact->writeable()->save();

        $user->allowedAbilities = []; // not authorized

        $this->actingAs($user, 'api');
        $response = $this->getJson("/contacts/{$contact->getKey()}");
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Unauthorized to view contact.'
        ]);
    }

    public function testStoreCreatesNewContactAndRedirects()
    {
        $fakeContactUuid = (string) Str::uuid();
        $contactServiceMock = Mockery::mock(ContactServiceContract::class);
        $contactServiceMock->shouldReceive('create')
            ->once()
            ->with('email', 'test@example.com')
            ->andReturn($fakeContactUuid);

        $this->app->instance(ContactServiceContract::class, $contactServiceMock);
        /** @var \Illuminate\Foundation\Auth\User */
        $user = User::factory()->create();
        $user->allowedAbilities = ['create'];
        $this->actingAs($user, 'api');

        $response = $this->post('/contacts', [
            'type'  => 'email',
            'value' => 'test@example.com',
        ]);

        $response->assertRedirect(route('contacts.show', $fakeContactUuid));
    }

    public function testDestroySoftDeletesContactForAuthorizedUser()
    {
        /** @var \Illuminate\Foundation\Auth\User */
        $user = User::factory()->create();
        $contact = Contact::factory()->make(
            [
                'is_primary' => false,
                'contactable_type' => $user->getMorphClass(),
                'contactable_id' => $user->getKey()
            ]
        );

        $contact->writeable()->save();

        $contactServiceMock = Mockery::mock(ContactServiceContract::class);
        $contactServiceMock->shouldReceive('destroy')
            ->once()
            ->with(Mockery::on(function ($arg) use ($contact) {
                return $arg->getKey() === $contact->getKey();
            }))
            ->andReturn(true);

        $this->app->instance(ContactServiceContract::class, $contactServiceMock);

        $user->allowedAbilities = ['delete'];
        $this->actingAs($user, 'api');

        $response = $this->deleteJson("/contacts/{$contact->getKey()}");
        $response->assertStatus(200);
        $response->assertJson(['deleted' => true]);
    }

    public function testDestroyReturns403ForUnauthorizedUser()
    {
        /** @var \Illuminate\Foundation\Auth\User */
        $user = User::factory()->create();
        $contact = Contact::factory()->make(
            [
                'is_primary' => false,
                'contactable_type' => $user->getMorphClass(),
                'contactable_id' => $user->getKey()
            ]
        );

        $contact->writeable()->save();
        $user->allowedAbilities = []; // not authorized
        $this->actingAs($user, 'api');

        $response = $this->deleteJson("/contacts/{$contact->getKey()}");
        $response->assertStatus(403);
        $response->assertJson(['message' => 'Unauthorized to delete contact.']);
    }

    public function testRestoreRestoresSoftDeletedContactForAuthorizedUser()
    {
        // Create a contact and soft-delete it.
        /** @var \Illuminate\Foundation\Auth\User */
        $user = User::factory()->create();
        $contact = Contact::factory()->make(
            [
                'is_primary' => false,
                'contactable_type' => $user->getMorphClass(),
                'contactable_id' => $user->getKey()
            ]
        );
        $contact->writeable()->save();
        $contact->writeable()->delete();

        $contactServiceMock = Mockery::mock(ContactServiceContract::class);
        $contactServiceMock->shouldReceive('restore')
            ->once()
            ->with($contact->uuid ?? $contact->getKey())
            ->andReturn(true);

        $this->app->instance(ContactServiceContract::class, $contactServiceMock);

        /** @var \Illuminate\Foundation\Auth\User */
        $user = User::factory()->create();
        $user->allowedAbilities = ['view'];
        $this->actingAs($user, 'api');

        $response = $this->postJson("/contacts/{$contact->getKey()}/restore");
        $response->assertStatus(200);
        $response->assertJson(['restored' => true]);
    }

    public function testRestoreReturns403ForUnauthorizedUser()
    {
        /** @var \Illuminate\Foundation\Auth\User */
        $user = User::factory()->create();
        $contact = Contact::factory()->make(
            [
                'is_primary' => false,
                'contactable_type' => $user->getMorphClass(),
                'contactable_id' => $user->getKey()
            ]
        );
        $contact->writeable()->save();

        $user->allowedAbilities = [];
        $this->actingAs($user, 'api');

        $response = $this->postJson("/contacts/{$contact->getKey()}/restore");
        $response->assertStatus(403);
        $response->assertJson(['message' => 'Unauthorized to restore contact.']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
