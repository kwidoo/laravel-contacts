<?php

namespace Kwidoo\Contacts;

use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Kwidoo\Contacts\Contracts\Contact;
use Kwidoo\Contacts\Contracts\ContactService as ContactServiceContract;
use Kwidoo\Contacts\Contracts\TokenGenerator as TokenGeneratorContract;
use Kwidoo\Contacts\Services\TokenGenerator;
use Kwidoo\Contacts\Services\VerificationService;
use Kwidoo\Contacts\Contracts\VerificationService as VerificationServiceContract;
use Kwidoo\Contacts\Services\ContactService;
use Kwidoo\Contacts\Services\EmailVerifier;
use Kwidoo\Contacts\Services\PhoneVerifier;

class ContactServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->publishes([
            __DIR__ . '/../config/contacts.php' => config_path('contacts.php'),
        ]);

        $this->loadRoutesFrom(__DIR__ . '/Http/routes.php');
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/contacts.php', 'contacts');

        $config = app()->make('config');
        $config->set('event-sourcing.projectors', array_merge(config('event-sourcing.projectors', []), [\Kwidoo\Contacts\Projectors\ContactProjector::class]));

        $this->app->bind(ContactServiceContract::class, ContactService::class);

        $this->app->bind(TokenGeneratorContract::class, TokenGenerator::class);

        $this->app->bind(VerificationServiceContract::class, function ($app, $params) {
            $contact = $params['contact'] ?? null;
            if (!$contact instanceof Contact) {
                throw new InvalidArgumentException('A valid Contact instance is required.');
            }

            $available = config('contacts.verifiers', []);
            if (!array_key_exists($contact->type, $available)) {
                throw new InvalidArgumentException("Unsupported contact type: {$contact->type}");
            }

            $verifier = $app->make($available[$contact->type], [
                'tokenGenerator' => $app->make(TokenGenerator::class, [
                    'contact' => $contact
                ]),
                'contact' => $contact
            ]);

            return new VerificationService($verifier, $contact);
        });
    }
}
