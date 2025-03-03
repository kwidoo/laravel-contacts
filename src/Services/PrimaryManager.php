<?php

namespace Kwidoo\Contacts\Services;

use Kwidoo\Contacts\Contracts\Contact;
use Kwidoo\Contacts\Aggregates\ContactAggregateRoot;
use InvalidArgumentException;
use Kwidoo\Contacts\Contracts\VerificationService;

/**
 * @property \Kwidoo\Contacts\Models\Contact $oldPrimary
 * @property \Kwidoo\Contacts\Models\Contact $newPrimary
 */
class PrimaryManager
{
    /**
     * @var VerificationService
     */
    protected VerificationService $oldVerification;

    /**
     * @var VerificationService
     */
    protected VerificationService $newVerification;

    public function __construct(
        protected Contact $oldPrimary,
        protected Contact $newPrimary
    ) {
        $this->validate();

        $this->oldVerification = app()->make(VerificationService::class, [
            'contact' => $oldPrimary
        ]);
        $this->newVerification = app()->make(VerificationService::class, [
            'contact' => $newPrimary
        ]);
    }

    /**
     * @return void
     */
    public function createChallenge(): void
    {
        $this->oldVerification->create();
        $this->newVerification->create();
    }

    /**
     * @param string $oldToken
     * @param string $newToken
     *
     * @return bool
     */
    public function verify(string $oldToken, string $newToken): bool
    {

        return $this->oldVerification->verify($oldToken) &&
            $this->newVerification->verify($newToken);
    }
    /**
     * @return void
     */
    public function swap(): void
    {
        ContactAggregateRoot::retrieve($this->oldPrimary->getKey())
            ->changePrimary($this->oldPrimary->getKey(), $this->newPrimary->getKey())
            ->persist();
    }

    /**
     * @return void
     */
    protected function validate(): void
    {
        if (!$this->oldPrimary->is_primary) {
            throw new InvalidArgumentException('Old primary contact is not primary');
        }

        if (!$this->newPrimary->is_verified) {
            throw new InvalidArgumentException('New primary contact is not verified');
        }

        if (!$this->newPrimary->contactable->is($this->oldPrimary->contactable)) {
            throw new InvalidArgumentException('New primary contact does not belong to the same contactable');
        }
    }
}
