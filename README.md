# Laravel Contacts

A Laravel package that provides a robust, event-sourced approach to managing multiple contact types (e.g., phone, email) for your Eloquent models. It offers features such as:

- Storing multiple contacts (phone/email, etc.) for a given model (e.g. `User`).
- Event-sourced creation, verification, and deletion of contacts.
- Configurable token-based verification mechanism (phone/email).
- Ability to designate primary contacts (only one primary at a time).
- Soft deletion with restore options.
- Support for UUIDs (configurable).
- Spatie Event Sourcing integration for auditing and replays.

## Table of Contents

1. [Installation](#installation)
2. [Configuration](#configuration)
3. [Migrations](#migrations)
4. [Usage](#usage)
   - [Adding the HasContacts Trait](#adding-the-hascontacts-trait)
   - [Creating Contacts](#creating-contacts)
   - [Listing Contacts](#listing-contacts)
   - [Verifying Contacts](#verifying-contacts)
   - [Deleting and Restoring Contacts](#deleting-and-restoring-contacts)
   - [Setting a Primary Contact](#setting-a-primary-contact)
4. [Event Sourcing](#event-sourcing)
5. [Advantages](#advantages)
6. [Additional Notes](#additional-notes)

---

## Installation

1. Require this package in your Laravel application:

   ```bash
   composer require kwidoo/contacts
   ```

2. This package also relies on [Spatie's Laravel Event Sourcing](https://spatie.be/docs/laravel-event-sourcing) under the hood. If you have not already installed and configured Spatie's event sourcing package, you should do so.

---

## Configuration

1. **Publish Config**:

   ```bash
   php artisan vendor:publish --provider="Kwidoo\Contacts\ContactServiceProvider" --tag="config"
   ```

   This will create a `config/contacts.php` file. Inside you will find the following key aspects:

   - **`model`** – The contact Eloquent model class (default: `Kwidoo\Contacts\Models\Contact`).
   - **`table`** – The database table for storing contacts (default: `contacts`).
   - **`uuid`** – Whether to use UUIDs as the primary key (boolean).
   - **`verifiers`** – A list of verifiers for different contact types (email, phone, etc.).
   - **`token`** – Configuration for token generation, including token length, TTL, and the token model/table.

2. **Bind Twilio or Other Services**:
   If you plan to use phone verification (`PhoneVerifier`), make sure you bind or configure any external SMS provider like Twilio. The example implementation references a `Kwidoo\MultiAuth\Services\TwilioService`. Adapt this service to your application’s needs.

3. **Event Sourcing**:
   This package automatically merges its required projector (`\Kwidoo\Contacts\Projectors\ContactProjector::class`) into your `event-sourcing.projectors` config. Make sure Spatie Event Sourcing is installed and configured appropriately.

---

## Migrations

After installing, run:

```bash
php artisan vendor:publish --provider="Kwidoo\Contacts\ContactServiceProvider" --tag="migrations"
php artisan migrate
```

This will publish and run the necessary migrations for storing contacts and tokens in your database.

---

## Usage

### Adding the HasContacts Trait

Any model (User, Company, etc.) that you wish to associate contacts with should implement `Kwidoo\Contacts\Contracts\Contactable` and use the `Kwidoo\Contacts\Traits\HasContacts` trait.

Example on a `User` model:

```php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Kwidoo\Contacts\Contracts\Contactable;
use Kwidoo\Contacts\Traits\HasContacts;

class User extends Authenticatable implements Contactable
{
    use HasContacts;

    // Your model implementation
}
```

> **Important**: The `HasContacts` trait adds a polymorphic relationship `contacts()` and also provides a `getPrimaryContactAttribute` accessor.

### Creating Contacts

You can create a contact through the provided controller or directly through the `ContactService`.

#### Via Controller and Routes

The package registers RESTful routes for `contacts`. You can do a POST request to `contacts` (by default at `/contacts`) with the request body containing:

```json
{
  "type": "email",
  "value": "user@example.com"
}
```

The validation ensures the `type` matches one of the configured verifiers (e.g., `email`, `phone`), and the `value` is unique for that model.

If successful, the route redirects to `contacts.show` (by default `/contacts/{contact}`).

#### Via `ContactService`

If you prefer direct usage in your code:

```php
use Kwidoo\Contacts\Contracts\ContactService;

// Typically, $this->user is your model that implements Contactable
$contactService = app(ContactService::class, [
    'model' => $this->user
]);

$uuid = $contactService->create('email', 'user@example.com');
// Returns the UUID (or ID) of the newly created contact
```

### Listing Contacts

Using the built-in controller route:

- `GET /contacts` – Lists all contacts, paginated.
- If you wish to only get the contacts for a specific model (e.g., a single user), you need to ensure proper authorization or build your own route logic to filter the contacts.

Or directly from your model:

```php
$user->contacts; // returns a collection of Contact models
```

### Verifying Contacts

Contacts can be verified via a token-based approach:

1. **Initiate Verification**:

   ```bash
   POST /contacts/verify/{contact}
   ```
   This calls `VerificationController@sendVerification`. It triggers a token generation and sends out the verification (via email or SMS, depending on the contact type).

2. **Complete Verification**:

   ```bash
   GET or POST /contacts/verify/{contact}/{token}
   ```
   This calls `VerificationController@verify`. If the token is valid, the `ContactVerified` event is recorded, and the contact is marked as verified.

### Deleting and Restoring Contacts

- **Delete (Soft Delete)**:

  ```bash
  DELETE /contacts/{contact}
  ```
  Calls the `ContactController@destroy` method, which uses `ContactService::destroy($contact)`.

  > **Note**: Attempting to delete a primary contact throws an exception.

- **Restore**:

  ```bash
  POST /contacts/restore/{uuid}
  ```
  Calls `ContactController@restore`, which invokes `ContactService::restore($uuid)`. This un-deletes the contact if it was soft-deleted.

### Setting a Primary Contact

By default, the **first** contact you create becomes the primary contact. You can change the primary contact via:

```bash
POST /contacts/{contact}/primary
```

This calls `ContactController@markAsPrimary`, leveraging the `ContactAggregateRoot` to update the primary contact.

---

## Event Sourcing

This package uses [Spatie's Event Sourcing](https://spatie.be/docs/laravel-event-sourcing) to record:

- **ContactCreated** – Fired when a new contact is created.
- **StartVerification** – Fired when verification is initiated for a contact.
- **ContactVerified** – Fired when a contact is successfully verified.
- **ContactDeleted** – Fired when a contact is soft-deleted.
- **PrimaryChanged** – Fired when the primary contact is changed.

The `ContactProjector` listens for these events and updates the read models (the `contacts` table). If you need to replay or track changes, you can use event sourcing commands (e.g., `php artisan event-sourcing:replay`) to rebuild state.

---

## Advantages

1. **Event-Sourced**: Full audit trail and ability to replay contact-related events.
2. **Multiple Contact Types**: Phone, email, or any custom type your application requires.
3. **Token-Based Verification**: Supports both email and phone verifications via token-based, OTP-like flows.
4. **Soft Delete + Restore**: Helps maintain history and preserves referential integrity.
5. **Primary Contact Management**: Ensures only one primary contact per model, with easy swaps.
6. **Configurable UUIDs**: Toggle between numeric IDs or UUIDs for your contacts.
7. **Extensibility**: Swappable verifiers, token generation, and external notification services (e.g., Twilio for SMS).
8. **Polymorphic**: Manage contacts for any Eloquent model (User, Company, etc.).

---

## Additional Notes

- If you need to implement custom verifiers, simply create a class that implements the `Kwidoo\Contacts\Contracts\Verifier` interface and add it to the `contacts.verifiers` array in the configuration.
- For phone verification, ensure you have a proper SMS provider service (like Twilio) configured and replace the existing reference `Kwidoo\MultiAuth\Services\TwilioService` with your own implementation if needed.
- Make sure your policies (`view`, `delete`, `update`, etc.) align with your application’s authorization logic. The sample routes use typical Laravel policy checks, but you can modify or remove them as needed.

Feel free to open issues or submit pull requests to enhance functionality or documentation. Enjoy managing your contacts in a more robust, scalable, and auditable fashion!
