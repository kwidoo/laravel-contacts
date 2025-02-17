<?php

namespace Kwidoo\Contacts\Collections;

use Illuminate\Database\Eloquent\Collection;
use Kwidoo\Contacts\Items\ContactItem;

class ContactCollection extends Collection
{
    public function all()
    {
        return array_map(
            function ($item) {
                return $item->values;
            },
            parent::all()
        );
    }
}
