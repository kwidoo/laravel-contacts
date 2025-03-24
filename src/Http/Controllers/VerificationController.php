<?php

namespace Kwidoo\Contacts\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Kwidoo\Contacts\Aggregates\ContactAggregateRoot;
use Kwidoo\Contacts\Contracts\Contact;
use Kwidoo\Contacts\Contracts\VerificationServiceFactory;
use Kwidoo\Contacts\Factories\RegistrationContext;

class VerificationController extends Controller
{
    public function __construct(protected VerificationServiceFactory $factory) {}
    /**
     * @param \Kwidoo\Contacts\Models\Contact $contact
     *
     * @return JsonResponse
     */
    public function sendVerification(Contact $contact): JsonResponse
    {
        $verificationService = $this->factory->make($contact, new RegistrationContext, ContactAggregateRoot::class);
        $verificationService->create();

        return response()->json([
            'message' => 'Verification sent'
        ]);
    }

    /**
     * @param \Kwidoo\Contacts\Models\Contact $contact
     * @param string $token
     *
     * @return JsonResponse
     */
    public function verify(Contact $contact, string $token): JsonResponse
    {
        $verificationService = $this->factory->make($contact, new RegistrationContext, ContactAggregateRoot::class);
        $verified = $verificationService->verify($token);

        if (!$verified) {
            return response()->json([
                'message' => 'Invalid token'
            ], 400);
        }

        return response()->json([
            'message' => 'Contact verified'
        ]);
    }
}
