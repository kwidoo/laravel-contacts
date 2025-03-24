<?php

namespace Kwidoo\Contacts\Services;

use Illuminate\Support\Facades\Notification;
use Kwidoo\Contacts\Contracts\Contact;
use Kwidoo\Contacts\Contracts\VerificationContext;
use Kwidoo\Contacts\Contracts\Verifier;
use Kwidoo\Contacts\Factories\GeneratorFactory;

/**
 * @property \Kwidoo\Contacts\Models\Contact $contact
 */
class EmailVerifier implements Verifier
{
    public function __construct(
        protected GeneratorFactory $factory,
        protected VerificationContext $context
    ) {}

    /**
     * @return void
     */
    public function create(Contact $contact): void
    {
        $template = $this->context->getTemplate($contact);
        $tokenGenerator = $this->factory->make($contact);
        /** @var \Kwidoo\Contacts\Models\Token */
        $token = $tokenGenerator->generate();

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
