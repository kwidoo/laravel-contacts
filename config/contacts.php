<?php

return [
    'types' => ['phone',  'email'],
    'verifiers' => [
        'phone' => \Kwidoo\Contacts\Services\PhoneVerifier::class,
        'email' => \Kwidoo\Contacts\Services\EmailVerifier::class,
    ],
    'contexts' => [
        'email' => \Kwidoo\Contacts\Factories\RegistrationContext::class,
        'phone' => \Kwidoo\Contacts\Factories\RegistrationContext::class, // customize
    ],

    // Use UUIDs for contacts?
    'uuid' => true,
    // Is User model using UUIDs?
    'uuidMorph' => true,
    'model' => \Kwidoo\Contacts\Models\Contact::class,
    'table' => 'contacts',

    // Token configuration
    'token' => [
        'length' => 6,
        'ttl' => 5,
        'model' => \Kwidoo\Contacts\Models\Token::class,
        'table' => 'tokens',
    ],

];
