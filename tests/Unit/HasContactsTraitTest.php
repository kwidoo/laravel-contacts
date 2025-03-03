<?php

namespace Kwidoo\Contacts\Tests\Unit;

use Kwidoo\Contacts\Tests\Fixtures\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Kwidoo\Contacts\Tests\TestCase;

class HasContactsTraitTest extends TestCase
{

    /** @test */
    public function contactsMethodReturnsAMorphManyRelationshipTest()
    {
        $user = User::factory()->create();
        $relation = $user->contacts();
        $this->assertInstanceOf(MorphMany::class, $relation);
    }

    /** @test */
    public function testGetPrimaryContactAttributeReturnsThePrimaryContact()
    {
        $user = User::factory()->create();

        // Create two contacts for this user.
        $primaryContact = $user->contacts()->create([
            'contactable_id'   => $user->id,
            'contactable_type' => $user->getMorphClass(),
            'type'             => 'email',
            'value'            => 'primary@example.com',
            'is_primary'       => true,
            'is_verified'      => true,
        ]);

        $user->contacts()->create([
            'contactable_id'   => $user->id,
            'contactable_type' => $user->getMorphClass(),
            'type'             => 'email',
            'value'            => 'secondary@example.com',
            'is_primary'       => false,
            'is_verified'      => false,
        ]);

        // Reload the relation to be sure.
        $user->load('contacts');

        // Access the primary contact using the accessor.
        $this->assertNotNull($user->primary_contact);
        $this->assertEquals($primaryContact->id, $user->primary_contact->id);
    }
}
