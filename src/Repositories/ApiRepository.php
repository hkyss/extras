<?php

namespace hkyss\Extras\Repositories;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use hkyss\Extras\Interfaces\RepositoryInterface;
use hkyss\Extras\Models\Extras;
use hkyss\Extras\Services\CacheService;

class ApiRepository implements RepositoryInterface
{
    private Client $httpClient;
    private string $apiUrl;
    private CacheService $cache;

    public function __construct(string $apiUrl = null, CacheService $cache = null)
    {
        $this->apiUrl = $apiUrl ?? config('extras.api_url', 'https://extras.evolutioncms.com/api');
        $this->cache = $cache ?? new CacheService(app('cache'));
        $this->httpClient = new Client([
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'EvolutionCMS-Extras/1.0',
                'Accept' => 'application/json',
            ]
        ]);
    }

    /**
     * @return Extras[]
     */
    public function getAll(): array
    {
        $cacheKey = "api_extras_all";
        
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        try {
            $response = $this->httpClient->get($this->apiUrl . '/extras');
            $data = json_decode($response->getBody()->getContents(), true);
            
            $extras = array_map(fn($item) => new Extras($item), $data['data'] ?? []);
            $this->cache->set($cacheKey, $extras, 3600);
            
            return $extras;
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Failed to fetch extras: ' . $e->getMessage());
        }
    }

    /**
     * @param string $packageName
     * @return Extras|null
     */
    public function find(string $packageName): ?Extras
    {
        $cacheKey = "api_extras_" . md5($packageName);
        
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        // Попробуем найти в кэше getAll() сначала
        $allCacheKey = "api_extras_all";
        if ($this->cache->has($allCacheKey)) {
            $allExtras = $this->cache->get($allCacheKey);
            foreach ($allExtras as $extra) {
                if ($extra->name === $packageName) {
                    // Кэшируем найденный пакет отдельно
                    $this->cache->set($cacheKey, $extra, 3600);
                    return $extra;
                }
            }
        }

        try {
            $response = $this->httpClient->get($this->apiUrl . '/extras/' . urlencode($packageName));
            $data = json_decode($response->getBody()->getContents(), true);
            
            $extra = new Extras($data);
            $this->cache->set($cacheKey, $extra, 3600);
            
            return $extra;
        } catch (GuzzleException $e) {
            return null;
        }
    }

    /**
     * @param string $search
     * @return Extras[]
     */
    public function search(string $search): array
    {
        $cacheKey = "api_extras_search_" . md5($search);
        
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        try {
            $response = $this->httpClient->get($this->apiUrl . '/extras/search', [
                'query' => ['q' => $search]
            ]);
            $data = json_decode($response->getBody()->getContents(), true);
            
            $extras = array_map(fn($item) => new Extras($item), $data['data'] ?? []);
            $this->cache->set($cacheKey, $extras, 3600);
            
            return $extras;
        } catch (GuzzleException $e) {
            return [];
        }
    }

    /**
     * @param array $filters
     * @return Extras[]
     */
    public function filter(array $filters): array
    {
        $cacheKey = "api_extras_filter_" . md5(serialize($filters));
        
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        try {
            $response = $this->httpClient->get($this->apiUrl . '/extras/filter', [
                'query' => $filters
            ]);
            $data = json_decode($response->getBody()->getContents(), true);
            
            $extras = array_map(fn($item) => new Extras($item), $data['data'] ?? []);
            $this->cache->set($cacheKey, $extras, 3600);
            
            return $extras;
        } catch (GuzzleException $e) {
            return [];
        }
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'API Repository';
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->apiUrl;
    }
}
