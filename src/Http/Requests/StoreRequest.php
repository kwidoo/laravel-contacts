<?php

namespace Kwidoo\Contacts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\Rules\Phone;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {

        return true;
    }

    public function rules(): array
    {
        $rules = [
            'type' => ['required', 'in:' . implode(',', array_keys(config('contacts.verifiers')))],
            'value' => [
                'required',
                'string',
                Rule::unique(config('contacts.table'), 'value')->whereNull('deleted_at')
            ],
        ];

        if ($this->input('type') === 'email') {
            $rules['value'][] = 'email:filter';
        } elseif ($this->input('type') === 'phone') {
            $rules['value'][] = (new Phone); // Requires a phone validator
        }

        return $rules;
    }
}
