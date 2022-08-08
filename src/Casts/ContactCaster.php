<?php

namespace Kwidoo\Contacts\Casts;

use Kwidoo\Contacts\Items\ContactItem;

class ContactCaster
{
    public static function castContactItem($value)
    {
        return (array)$value;
    }
}
