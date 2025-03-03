<?php

use Illuminate\Support\Facades\Route;
use Kwidoo\Contacts\Http\Controllers\ContactController;
use Kwidoo\Contacts\Http\Controllers\VerificationController;

Route::match(
    ['get', 'post'],
    'contacts/verify/{contact}/{token}',
    [
        VerificationController::class,
        'verify'
    ]
)->name('contacts.verify');


Route::group(['middleware' => ['auth:api']], function () {
    Route::post('contacts/verify/{contact}', [
        VerificationController::class,
        'sendVerification'
    ])->name('contacts.sendVerification');


    Route::resource('contacts', ContactController::class);


    Route::post('contacts/{contact}/primary', [ContactController::class, 'markAsPrimary'])
        ->name('contacts.markAsPrimary');
});
