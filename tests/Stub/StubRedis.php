<?php

namespace App\Tests\Stub;

class StubRedis
{
    private array $storage = [];
    private array $expirations = [];

    public function connect(string $host, int $port): bool
    {
        // Всегда успешно
        return true;
    }

    public function get(string $key): mixed
    {
        if (isset($this->expirations[$key]) && $this->expirations[$key] < time()) {
            unset($this->storage[$key], $this->expirations[$key]);
            return false;
        }
        return $this->storage[$key] ?? false;
    }

    public function set(string $key, mixed $value, int $ttl = 0): bool
    {
        $this->storage[$key] = $value;
        if ($ttl > 0) {
            $this->expirations[$key] = time() + $ttl;
        }
        return true;
    }
    
     public function del(string $key): mixed
    {
        if (isset($this->storage[$key])) {
            unset($this->storage[$key], $this->expirations[$key]);
            return true;
        }
        return false;
    }

    public function save(): bool
    {
        // в реальном Redis это сохраняет на диск, тут ничего
        return true;
    }
}
