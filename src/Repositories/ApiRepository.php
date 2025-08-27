<?php

namespace EvolutionCMS\Extras\Repositories;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use EvolutionCMS\Extras\Interfaces\ExtrasRepositoryInterface;
use EvolutionCMS\Extras\Models\Extras;

class ApiRepository implements ExtrasRepositoryInterface
{
    private Client $httpClient;
    private string $apiUrl;

    public function __construct(string $apiUrl = null)
    {
        $this->apiUrl = $apiUrl ?? config('extras.api_url', 'https://extras.evolutioncms.com/api');
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
        try {
            $response = $this->httpClient->get($this->apiUrl . '/extras');
            $data = json_decode($response->getBody()->getContents(), true);
            
            return array_map(fn($item) => new Extras($item), $data['data'] ?? []);
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
        try {
            $response = $this->httpClient->get($this->apiUrl . '/extras/' . urlencode($packageName));
            $data = json_decode($response->getBody()->getContents(), true);
            
            return new Extras($data);
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
        try {
            $response = $this->httpClient->get($this->apiUrl . '/extras/search', [
                'query' => ['q' => $search]
            ]);
            $data = json_decode($response->getBody()->getContents(), true);
            
            return array_map(fn($item) => new Extras($item), $data['data'] ?? []);
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
        try {
            $response = $this->httpClient->get($this->apiUrl . '/extras/filter', [
                'query' => $filters
            ]);
            $data = json_decode($response->getBody()->getContents(), true);
            
            return array_map(fn($item) => new Extras($item), $data['data'] ?? []);
        } catch (GuzzleException $e) {
            return [];
        }
    }
}
