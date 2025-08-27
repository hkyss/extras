<?php

namespace EvolutionCMS\Extras\Repositories;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use EvolutionCMS\Extras\Interfaces\RepositoryInterface;
use EvolutionCMS\Extras\Models\Extras;
use EvolutionCMS\Extras\Services\CacheService;

class GitHubRepository implements RepositoryInterface
{
    private Client $httpClient;
    private string $apiUrl;
    private string $organization;
    private string $name;
    private CacheService $cache;

    public function __construct(string $organization, string $name = 'GitHub', CacheService $cache = null)
    {
        $this->organization = $organization;
        $this->name = $name;
        $this->apiUrl = 'https://api.github.com';
        $this->cache = $cache ?? new CacheService(app('cache'));
        
        $this->httpClient = new Client([
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'EvolutionCMS-Extras/1.0',
                'Accept' => 'application/vnd.github.v3+json',
            ]
        ]);
    }

        /**
     * @return Extras[]
     */
    public function getAll(): array
    {
        $cacheKey = "github_repos_{$this->organization}";
        
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        try {
            $response = $this->httpClient->get($this->apiUrl . '/orgs/' . $this->organization . '/repos');
            $repos = json_decode($response->getBody()->getContents(), true);
            
            if (!is_array($repos)) {
                throw new \RuntimeException('Invalid response from GitHub API');
            }
            
            $extras = [];
            foreach ($repos as $repo) {
                if ($this->isValidExtra($repo)) {
                    $extras[] = $this->createExtraFromRepo($repo);
                }
            }
            
            $this->cache->set($cacheKey, $extras, 3600);
            return $extras;
        } catch (GuzzleException $e) {
            return [];
        }
    }

    /**
     * @param string $packageName
     * @return Extras|null
     */
    public function find(string $packageName): ?Extras
    {
        try {
            $response = $this->httpClient->get($this->apiUrl . '/repos/' . $this->organization . '/' . $packageName);
            $repo = json_decode($response->getBody()->getContents(), true);

            if ($this->isValidExtra($repo)) {
                return $this->createExtraFromRepo($repo);
            }

            return null;
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
            $response = $this->httpClient->get($this->apiUrl . '/search/repositories', [
                'query' => [
                    'q' => 'org:' . $this->organization . ' ' . $search,
                    'sort' => 'stars',
                    'order' => 'desc'
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $extras = [];

            foreach ($data['items'] ?? [] as $repo) {
                if ($this->isValidExtra($repo)) {
                    $extras[] = $this->createExtraFromRepo($repo);
                }
            }

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
        return $this->name;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return 'https://github.com/' . $this->organization;
    }

    /**
     * @param array $repo
     * @return bool
     */
    private function isValidExtra(array $repo): bool
    {
        return !$repo['archived'];
    }

    /**
     * @param array $repo
     * @return bool
     */
    private function hasComposerJson(array $repo): bool
    {
        try {
            $response = $this->httpClient->get($this->apiUrl . '/repos/' . $repo['full_name'] . '/contents/composer.json');
            return $response->getStatusCode() === 200;
        } catch (GuzzleException $e) {
            return false;
        }
    }

    /**
     * @param array $repo
     * @return Extras
     */
    private function createExtraFromRepo(array $repo): Extras
    {
        $composerData = $this->getComposerData($repo['full_name']);

        return new Extras([
            'name' => $composerData['name'] ?? $repo['full_name'],
            'description' => $repo['description'] ?? '',
            'version' => $this->getLatestRelease($repo['full_name']),
            'author' => $repo['owner']['login'] ?? '',
            'license' => $repo['license']['spdx_id'] ?? 'MIT',
            'keywords' => $composerData['keywords'] ?? [],
            'homepage' => $repo['homepage'] ?? $repo['html_url'],
            'require' => $composerData['require'] ?? [],
            'suggest' => $composerData['suggest'] ?? [],
            'type' => $composerData['type'] ?? 'library',
            'dist' => [
                'url' => $repo['clone_url']
            ],
            'source' => [
                'url' => $repo['html_url']
            ],
            'support' => $composerData['support'] ?? [],
            'autoload' => $composerData['autoload'] ?? [],
            'extra' => $composerData['extra'] ?? [],
            'created_at' => $repo['created_at'],
            'updated_at' => $repo['updated_at'],
        ]);
    }

    /**
     * @param string $fullName
     * @return array
     */
    private function getComposerData(string $fullName): array
    {
        try {
            $response = $this->httpClient->get($this->apiUrl . '/repos/' . $fullName . '/contents/composer.json');
            $content = json_decode($response->getBody()->getContents(), true);
            
            if (isset($content['content'])) {
                return json_decode(base64_decode($content['content']), true) ?: [];
            }
        } catch (GuzzleException $e) {
            // 
        }
        
        return [];
    }

    /**
     * @param string $fullName
     * @return string
     */
    private function getLatestRelease(string $fullName): string
    {
        try {
            $response = $this->httpClient->get($this->apiUrl . '/repos/' . $fullName . '/releases/latest');
            $release = json_decode($response->getBody()->getContents(), true);
            
            return $release['tag_name'] ?? 'dev-master';
        } catch (GuzzleException $e) {
            return 'dev-master';
        }
    }
}
