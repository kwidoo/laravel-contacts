<?php

namespace Kwidoo\Contacts\Services;

use Illuminate\Database\Eloquent\Model;
use Kwidoo\Contacts\Contracts\Contact;
use Kwidoo\Contacts\Contracts\TokenGenerator as TokenGeneratorContract;

/**
 * @property \Kwidoo\Contacts\Models\Contact $contact
 */
class TokenGenerator implements TokenGeneratorContract
{
    /**
     * @var \Kwidoo\Contacts\Models\Contact
     */

    public function __construct(protected Contact $contact) {}

    /**
     * @param string $value
     *
     * @return string
     */
    public function generate(): Model
    {
        return $this->contact->tokens()->create([
            'token' => $this->generateToken(),
            'method' => $this->contact->type,
            'expires_at' => now()->addMinutes(config('contacts.token.ttl')),
        ]);
    }

    /**
     * @return string
     */
    protected function generateToken(): string
    {
        $length = config('contacts.token.length');
        return str_pad((string)rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }
}
