<?php

namespace EvolutionCMS\Extras\Services;

use Illuminate\Cache\CacheManager;

class CacheService
{
    private CacheManager $cache;
    private int $ttl;

    public function __construct(CacheManager $cache)
    {
        $this->cache = $cache;
        $this->ttl = config('extras.cache.ttl', 3600);
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function get(string $key)
    {
        return $this->cache->get($key);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @return void
     */
    public function set(string $key, $value, int $ttl = null): void
    {
        $ttl = $ttl ?? $this->ttl;
        $this->cache->put($key, $value, $ttl);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->cache->has($key);
    }

    /**
     * @param string $key
     * @return void
     */
    public function delete(string $key): void
    {
        $this->cache->forget($key);
    }

    /**
     * @return void
     */
    public function clear(): void
    {
        $this->cache->flush();
    }
}
