<?php

namespace Kwidoo\Contacts\Tests\Fakes;

use Kwidoo\Contacts\Contracts\Contactable;
use Illuminate\Support\Collection;

class FakeContactable implements Contactable
{
    protected int|string $key;

    public function __construct($key)
    {
        $this->key = $key;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function getMorphClass()
    {
        return 'FakeContactable';
    }

    // For testing, we return an empty collection.
    public function contacts()
    {
        return new Collection();
    }
}
