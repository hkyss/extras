<?php

namespace EvolutionCMS\Extras\Interfaces;

use EvolutionCMS\Extras\Models\Extras;

interface RepositoryInterface
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
     * @return string
     */
    public function getName(): string;

    /**
     * @return string
     */
    public function getUrl(): string;
}
