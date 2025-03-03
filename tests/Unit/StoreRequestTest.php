<?php

namespace Kwidoo\Contacts\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Kwidoo\Contacts\Http\Requests\StoreRequest;
use Kwidoo\Contacts\Tests\TestCase;

class StoreRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper to get validation rules from the StoreRequest.
     */
    protected function getRules(array $data = []): array
    {
        $request = new StoreRequest();
        $request->merge($data);
        return $request->rules();
    }

    public function testValidEmailInputPassesValidation()
    {
        $data = [
            'type'  => 'email',
            'value' => 'test@example.com',
        ];

        $rules = $this->getRules($data);
        $validator = Validator::make($data, $rules);

        $this->assertTrue($validator->passes(), 'Valid email should pass validation.');
    }

    public function testValidPhoneInputPassesValidation()
    {
        $data = [
            'type'  => 'phone',
            'value' => '+1234567890', // a valid phone number format for the phone validator
        ];

        $rules = $this->getRules($data);
        $validator = Validator::make($data, $rules);

        $this->assertTrue($validator->passes(), 'Valid phone should pass validation.');
    }

    public function testInvalidContactTypeFailsValidation()
    {
        $data = [
            'type'  => 'fax', // unsupported type
            'value' => 'test@example.com',
        ];

        $rules = $this->getRules($data);
        $validator = Validator::make($data, $rules);

        $this->assertFalse($validator->passes(), 'Invalid contact type should fail validation.');
        $this->assertArrayHasKey('type', $validator->errors()->toArray());
    }

    public function testInvalidEmailFormatFailsValidation()
    {
        $data = [
            'type'  => 'email',
            'value' => 'not-an-email',
        ];

        $rules = $this->getRules($data);
        $validator = Validator::make($data, $rules);

        $this->assertFalse($validator->passes(), 'Improperly formatted email should fail validation.');
        $this->assertArrayHasKey('value', $validator->errors()->toArray());
    }

    public function testInvalidPhoneFormatFailsValidation()
    {
        $data = [
            'type'  => 'phone',
            'value' => 'invalid-phone',
        ];

        $rules = $this->getRules($data);
        $validator = Validator::make($data, $rules);

        $this->assertFalse($validator->passes(), 'Improperly formatted phone should fail validation.');
        $this->assertArrayHasKey('value', $validator->errors()->toArray());
    }

    public function testUniquenessConstraintPreventsDuplicates()
    {
        // Insert a record manually into the contacts table.
        \DB::table(config('contacts.table'))->insert([
            'uuid'              => Str::uuid()->toString(),
            'contactable_type'  => 'DummyUser',  // adjust as needed
            'contactable_id'    => 1,
            'type'              => 'email',
            'value'             => 'unique@example.com',
            'is_primary'        => false,
            'is_verified'       => false,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $data = [
            'type'  => 'email',
            'value' => 'unique@example.com',
        ];

        $rules = $this->getRules($data);
        $validator = Validator::make($data, $rules);

        $this->assertFalse($validator->passes(), 'Duplicate contact should fail the uniqueness rule.');
        $this->assertArrayHasKey('value', $validator->errors()->toArray());
    }
}
