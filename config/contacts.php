<?php

return [
    'types' => ['phone',  'email'],
    'verifiers' => [
        'phone' => \Kwidoo\SmsVerification\Verifiers\TwilioVerifier::class,
        'email' => \Kwidoo\Contacts\Services\EmailVerifier::class,
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
