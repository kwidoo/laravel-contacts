<?php

namespace Kwidoo\Contacts\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Kwidoo\Contacts\Http\Resources\ContactResource;
use Kwidoo\Contacts\Contracts\ContactService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controller;
use Kwidoo\Contacts\Http\Requests\StoreRequest;
use Kwidoo\Contacts\Models\Contact;
use Kwidoo\Contacts\Contracts\Contactable;

class ContactController extends Controller
{
    /**
     * Display a listing of the contacts.
     *
     * @param Request $request
     * @param Contactable|null $contactable
     * @return JsonResource|JsonResponse
     */
    public function index(Request $request, ?Contactable $contactable = null)
    {
        if ($request->user()->can('viewAny', Contact::class)) {
            return ContactResource::collection(config('contacts.model')::paginate());
        }

        if (
            $contactable &&
            ($request->user()->can('view', $contactable) || $request->user()->is($contactable))
        ) {
            return ContactResource::collection(
                $contactable->contacts()->paginate()
            );
        }

        // Fallback response if the user is not authorized
        return response()->json([
            'message' => 'Unauthorized to view contacts.'
        ], 403);
    }

    /**
     * Show a specific contact.
     *
     * @param Request $request
     * @param Contact $contact
     * @return JsonResponse|ContactResource
     */
    public function show(Request $request, Contact $contact)
    {
        if (!$request->user()->can('view', $contact->contactable)) {
            return response()->json([
                'message' => 'Unauthorized to view contact.'
            ], 403);
        }

        return new ContactResource($contact);
    }

    /**
     * Store a newly created contact.
     *
     * @param StoreRequest $request
     */
    public function store(StoreRequest $request)
    {
        $contactService = app()->make(ContactService::class, [
            'model' => $request->user()
        ]);

        $contact = $contactService->create(
            $request->get('type'),
            $request->get('value')
        );

        return redirect()->route('contacts.show', $contact);
    }

    /**
     * Delete (soft-delete) a specific contact.
     *
     * @param Request $request
     * @param Contact $contact
     * @return JsonResponse
     */
    public function destroy(Request $request, Contact $contact): JsonResponse
    {
        if (!$request->user()->can('delete', $contact->contactable)) {
            return response()->json([
                'message' => 'Unauthorized to delete contact.'
            ], 403);
        }

        $deleted = app()->make(ContactService::class, [
            'model' => $request->user()
        ])->destroy($contact);

        return response()->json(['deleted' => $deleted]);
    }

    /**
     * Restore a soft-deleted contact by UUID.
     *
     * @param Request $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function restore(Request $request, string $uuid): JsonResponse
    {
        if (!$request->user()->can('view', $request->user())) {
            return response()->json([
                'message' => 'Unauthorized to restore contact.'
            ], 403);
        }

        $restored = app()->make(ContactService::class, [
            'model' => $request->user()
        ])->restore($uuid);

        return response()->json(['restored' => $restored]);
    }
}
