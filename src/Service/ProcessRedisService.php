<?php

namespace App\Service;

use App\Service\ProcessRedisServiceInterface;

class ProcessRedisService extends \Redis implements ProcessRedisServiceInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->connect($_ENV['REDIS_HOST'] ?? 'symfony_redis', (int)($_ENV['REDIS_PORT'] ?? 6379));
    }

    public function set(string $key, mixed $value, mixed $options = null): \Redis|string|bool
    {
        return parent::set($key, $value, $options);
    }

    public function get(string $key): mixed
    {
        return parent::get($key);
    }

    public function save(): bool
    {
        return parent::save();
    }
}

