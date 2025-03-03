<?php

namespace Kwidoo\Contacts\Tests\Fixtures;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Kwidoo\Contacts\Contracts\Contactable;
use Kwidoo\Contacts\Traits\HasContacts;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Kwidoo\Database\Factories\UserFactory;

class User extends Authenticatable implements Contactable
{
    use HasContacts;
    use HasFactory;

    public function getKeyName()
    {
        return 'uuid';
    }

    public function getKeyType()
    {
        return 'string';
    }

    public $incrementing = false;


    protected static function newFactory()
    {
        return UserFactory::new();
    }
}
