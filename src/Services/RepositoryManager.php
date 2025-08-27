<?php

namespace EvolutionCMS\Extras\Services;

use EvolutionCMS\Extras\Interfaces\RepositoryInterface;
use EvolutionCMS\Extras\Models\Extras;

class RepositoryManager
{
    private array $repositories = [];

    /**
     * @param RepositoryInterface $repository
     * @return void
     */
    public function addRepository(RepositoryInterface $repository): void
    {
        $this->repositories[] = $repository;
    }

    /**
     * @return RepositoryInterface[]
     */
    public function getRepositories(): array
    {
        return $this->repositories;
    }

    /**
     * @return Extras[]
     */
    public function getAllExtras(): array
    {
        $allExtras = [];
        
        foreach ($this->repositories as $repository) {
            try {
                $extras = $repository->getAll();
                foreach ($extras as $extra) {
                    $extra->repository = $repository->getName();
                    $allExtras[] = $extra;
                }
            } catch (\Exception $e) {
                // Log error but continue with other repositories
                continue;
            }
        }
        
        return $allExtras;
    }

    /**
     * @param string $packageName
     * @return Extras|null
     */
    public function findExtra(string $packageName): ?Extras
    {
        foreach ($this->repositories as $repository) {
            try {
                $extra = $repository->find($packageName);
                if ($extra) {
                    $extra->repository = $repository->getName();
                    return $extra;
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        
        return null;
    }

    /**
     * @param string $search
     * @return Extras[]
     */
    public function searchExtras(string $search): array
    {
        $results = [];
        
        foreach ($this->repositories as $repository) {
            try {
                $extras = $repository->search($search);
                foreach ($extras as $extra) {
                    $extra->repository = $repository->getName();
                    $results[] = $extra;
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        
        return $results;
    }

    /**
     * @param string $repositoryName
     * @return RepositoryInterface|null
     */
    public function getRepository(string $repositoryName): ?RepositoryInterface
    {
        foreach ($this->repositories as $repository) {
            if ($repository->getName() === $repositoryName) {
                return $repository;
            }
        }
        
        return null;
    }

    /**
     * @return array
     */
    public function getRepositoryInfo(): array
    {
        $info = [];
        
        foreach ($this->repositories as $repository) {
            $info[] = [
                'name' => $repository->getName(),
                'url' => $repository->getUrl(),
            ];
        }
        
        return $info;
    }
}
