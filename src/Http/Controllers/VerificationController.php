<?php

namespace Kwidoo\Contacts\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Kwidoo\Contacts\Contracts\Contact;
use Kwidoo\Contacts\Contracts\VerificationService;

class VerificationController extends Controller
{
    /**
     * @param \Kwidoo\Contacts\Models\Contact $contact
     *
     * @return JsonResponse
     */
    public function sendVerification(Contact $contact): JsonResponse
    {
        $this->verificationService($contact)->create();

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
        $verified = $this->verificationService($contact)->verify($token);

        if (!$verified) {
            return response()->json([
                'message' => 'Invalid token'
            ], 400);
        }

        return response()->json([
            'message' => 'Contact verified'
        ]);
    }

    /**
     * @param \Kwidoo\Contacts\Models\Contact $contact
     *
     * @return VerificationService
     */
    protected function verificationService(Contact $contact): VerificationService
    {
        return app()->make(VerificationService::class, [
            'contact' => $contact
        ]);
    }
}
