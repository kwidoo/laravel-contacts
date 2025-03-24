<?php

namespace Kwidoo\Contacts\Factories;

use Kwidoo\Contacts\Contracts\ContactService;
use Kwidoo\Contacts\Contracts\ContactServiceFactory as ContactServiceFactoryContract;
use Kwidoo\Contacts\Contracts\Contactable;

class ContactServiceFactory implements ContactServiceFactoryContract
{
    public function make(Contactable $model): ContactService
    {
        return app()->make(ContactService::class, ['model' => $model]);
    }
}
