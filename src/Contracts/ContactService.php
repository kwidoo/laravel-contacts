<?php

namespace Kwidoo\Contacts\Contracts;

interface ContactService
{
    public function create(string $type, string $value): string|int;

    public function delete(string $id): bool;

    public function restore(string $id): bool;
}
