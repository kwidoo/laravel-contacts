<?php

namespace Kwidoo\Contacts\Services;

use Illuminate\Support\Facades\Notification;
use Kwidoo\Contacts\Contracts\Contact;
use Kwidoo\Contacts\Contracts\TokenGenerator;
use Kwidoo\Contacts\Notifications\TokenNotification;
use Kwidoo\Contacts\Contracts\Verifier;

/**
 * @property \Kwidoo\Contacts\Models\Contact $contact
 */
class EmailVerifier implements Verifier
{
    public function __construct(
        protected TokenGenerator $tokenGenerator,
        protected Contact $contact
    ) {}

    /**
     * @return void
     */
    public function create(): void
    {
        /** @var \Kwidoo\Contacts\Models\Token */
        $token = $this->tokenGenerator->generate();

        Notification::route('mail', $this->contact->value)
            ->notify(
                app()->make(TokenNotification::class, [
                    'token' => $token->token
                ])
            );
    }

    /**
     * @param string $token
     *
     * @return bool
     */
    public function verify(string $token): bool
    {
        $foundToken = $this->contact
            ->tokens()
            ->where('token', $token)
            ->isNotExpired()
            ->isNotVerified()
            ->first();

        if (!$token) {
            return false;
        }

        $foundToken->update(['verified_at' => now()]);

        return true;
    }
}
