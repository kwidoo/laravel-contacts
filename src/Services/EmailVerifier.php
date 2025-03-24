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

    ) {}

    /**
     * @return void
     */
    public function create(Contact $contact, ?string $template = null): void
    {
        if (!$template) {
            $template = TokenNotification::class;
        }
        /** @var \Kwidoo\Contacts\Models\Token */
        $token = $this->tokenGenerator->generate();

        Notification::route('mail', $contact->value)
            ->notify(
                app()->make($template, [
                    'token' => $token->token
                ])
            );
    }

    /**
     * @param string $token
     *
     * @return bool
     */
    public function verify(Contact $contact, string $token): bool
    {
        $foundToken = $contact
            ->tokens()
            ->where('token', $token)
            ->isNotExpired()
            ->isNotVerified()
            ->firstOrFail();

        if (!$token) {
            return false;
        }

        $foundToken->update(['verified_at' => now()]);

        return true;
    }
}
