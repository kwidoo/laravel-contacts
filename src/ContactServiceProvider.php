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
use Kwidoo\Contacts\Factories\VerificationServiceFactory;
use Kwidoo\Contacts\Contracts\VerificationServiceFactory as VerificationServiceFactoryContract;
use Kwidoo\Contacts\Factories\ContactServiceFactory;
use Kwidoo\Contacts\Contracts\ContactServiceFactory as ContactServiceFactoryContract;
use Illuminate\Support\Facades\Route;
use Kwidoo\Contacts\Contracts\VerifierFactory as VerifierFactoryContract;
use Kwidoo\Contacts\Factories\VerifierFactory;

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

        $this->app->bind(Contact::class, config('contacts.model'));
        Route::model('contact', config('contacts.model'));

        $this->app->bind(ContactServiceContract::class, ContactService::class);
        $this->app->bind(ContactServiceFactoryContract::class, ContactServiceFactory::class);
        $this->app->bind(VerificationServiceFactoryContract::class, VerificationServiceFactory::class);
        $this->app->bind(VerificationServiceContract::class, VerificationService::class);
        $this->app->bind(VerifierFactoryContract::class, VerifierFactory::class);
        $this->app->bind(TokenGeneratorContract::class, TokenGenerator::class);
    }
}
