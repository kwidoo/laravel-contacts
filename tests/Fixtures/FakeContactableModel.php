<?php

namespace Kwidoo\Contacts\Tests\Fakes;

use Illuminate\Database\Eloquent\Model;
use Kwidoo\Contacts\Traits\HasContacts;

class FakeContactableModel extends Model
{
    use HasContacts;

    protected $table = 'fake_contactables';
    protected $guarded = [];
}
