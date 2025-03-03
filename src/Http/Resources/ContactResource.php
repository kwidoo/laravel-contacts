<?php

namespace Kwidoo\Contacts\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->when(!config('contacts.uuid'), $this->id),
            'uuid' => $this->when(config('contacts.uuid'), $this->uuid),
            'type' => $this->type,
            'value' => $this->value,
            'is_primary' => $this->is_primary,
            'is_verified' => $this->is_verified,
        ];
    }
}
