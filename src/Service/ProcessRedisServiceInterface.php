<?php

namespace App\Service;

interface ProcessRedisServiceInterface
{
    public function set(string $key, mixed $value, mixed $options = null): \Redis|string|bool;

    public function get(string $key): mixed;

    public function save(): bool;
}

