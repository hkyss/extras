<?php

namespace EvolutionCMS\Extras\Interfaces;

use EvolutionCMS\Extras\Models\Extras;

interface ExtrasRepositoryInterface
{
    /**
     * @return Extras[]
     */
    public function getAll(): array;

    /**
     * @param string $packageName
     * @return Extras|null
     */
    public function find(string $packageName): ?Extras;

    /**
     * @param string $search
     * @return Extras[]
     */
    public function search(string $search): array;

    /**
     * @param array $filters
     * @return Extras[]
     */
    public function filter(array $filters): array;
}
