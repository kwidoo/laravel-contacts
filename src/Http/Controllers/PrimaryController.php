<?php

namespace Kwidoo\Contacts\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Kwidoo\Contacts\Aggregates\ContactAggregateRoot;
use Kwidoo\Contacts\Contracts\Contact;
use Kwidoo\Contacts\Services\PrimaryManager;

class PrimaryController extends Controller
{
    /**
     * Send a verification challenge for swapping primary contacts.
     *
     * @param Contact $oldContact
     * @param Contact $newContact
     * @return JsonResponse
     */
    public function sendChallenge(Contact $oldContact, Contact $newContact): JsonResponse
    {
        $this->authorize('swap', $oldContact);

        $primaryManager = $this->primaryManager($oldContact, $newContact);

        $primaryManager->createChallenge();

        return response()->json([
            'message' => 'Verification challenge sent',
        ]);
    }

    /**
     * Swap the primary contact, optionally requiring a challenge.
     *
     * @param Request $request
     * @param Contact $oldContact
     * @param Contact $newContact
     * @return JsonResponse
     */
    public function swap(Request $request, Contact $oldContact, Contact $newContact): JsonResponse
    {
        $this->authorize('swap', $oldContact);

        $primaryManager = $this->primaryManager($oldContact, $newContact);

        $shouldChallenge = $request->user()->can('swapWithoutChallenge', $oldContact);

        if (!$shouldChallenge && !$this->verifyChallenge($request, $primaryManager)) {
            return response()->json([
                'message' => 'Invalid verification tokens',
            ], 400);
        }

        // Perform swap
        $primaryManager->swap();

        return response()->json([
            'message' => 'Primary contact updated successfully',
        ]);
    }

    public function markAsPrimary(Request $request, Contact $contact)
    {
        if (!$request->user()->can('update', $contact->contactable)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Assuming a service or direct aggregate interaction
        ContactAggregateRoot::retrieve($contact->uuid)
            ->changePrimary($contact->contactable->getPrimaryContact()->uuid, $contact->uuid)
            ->persist();

        return response()->json(['message' => 'Contact marked as primary']);
    }

    /**
     * @param Contact $oldContact
     * @param Contact $newContact
     *
     * @return PrimaryManager
     */
    protected function primaryManager(Contact $oldContact, Contact $newContact): PrimaryManager
    {
        return app()->make(PrimaryManager::class, [
            'oldPrimary' => $oldContact,
            'newPrimary' => $newContact
        ]);
    }

    /**
     * @param Request $request
     * @param PrimaryManager $primaryManager
     *
     * @return bool
     */
    protected function verifyChallenge(Request $request, PrimaryManager $primaryManager): bool
    {
        $request->validate([
            'old_token' => ['required', 'string'],
            'new_token' => ['required', 'string'],
        ]);

        return $primaryManager->verify($request->input('old_token'), $request->input('new_token'));
    }
}
