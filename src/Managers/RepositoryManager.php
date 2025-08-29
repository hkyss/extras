<?php

namespace EvolutionCMS\Extras\Managers;

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
     * @return RepositoryInterface[]
     */
    public function getUniqueRepositories(): array
    {
        $uniqueRepositories = [];
        $seenNames = [];
        
        foreach ($this->repositories as $repository) {
            $name = $repository->getName();
            if (!in_array($name, $seenNames)) {
                $uniqueRepositories[] = $repository;
                $seenNames[] = $name;
            }
        }
        
        return $uniqueRepositories;
    }

    /**
     * @return Extras[]
     */
    public function getAllExtras(): array
    {
        $allExtras = [];
        $seenPackages = [];
        
        foreach ($this->repositories as $repository) {
            try {
                $extras = $repository->getAll();
                foreach ($extras as $extra) {
                    $extra->repository = $repository->getName();
                    
                    $packageKey = $extra->name;
                    if (!isset($seenPackages[$packageKey])) {
                        $seenPackages[$packageKey] = true;
                        $allExtras[] = $extra;
                    }
                }
            } catch (\Exception $e) {
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
        $seenPackages = [];
        
        foreach ($this->repositories as $repository) {
            try {
                $extras = $repository->search($search);
                foreach ($extras as $extra) {
                    $extra->repository = $repository->getName();
                    
                    // Проверяем, не видели ли мы уже этот пакет
                    $packageKey = $extra->name;
                    if (!isset($seenPackages[$packageKey])) {
                        $seenPackages[$packageKey] = true;
                        $results[] = $extra;
                    }
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
