<?php

namespace EvolutionCMS\Extras\Services;

use EvolutionCMS\Extras\Models\Extras;
use EvolutionCMS\Extras\Interfaces\ExtrasRepositoryInterface;
use EvolutionCMS\Extras\Interfaces\PackageManagerInterface;
use EvolutionCMS\Extras\Exceptions\PackageNotFoundException;
use EvolutionCMS\Extras\Exceptions\InstallationException;

class ExtrasService
{
    private ExtrasRepositoryInterface $repository;
    private PackageManagerInterface $packageManager;
    private string $cachePath;

    public function __construct(ExtrasRepositoryInterface $repository, PackageManagerInterface $packageManager = null)
    {
        $this->repository = $repository;
        $this->packageManager = $packageManager ?? new \EvolutionCMS\Extras\Managers\ComposerPackageManager();
        $this->cachePath = config('extras.cache.path', EVO_CORE_PATH . 'cache/extras/');
        $this->ensureCacheDirectory();
    }

    /**
     * @return Extras[]
     */
    public function getAvailableExtras(): array
    {
        return $this->repository->getAll();
    }

    /**
     * @param string $packageName
     * @return Extras|null
     */
    public function getExtra(string $packageName): ?Extras
    {
        return $this->repository->find($packageName);
    }

    /**
     * @param string $packageName
     * @param string $version
     * @return bool
     */
    public function installExtra(string $packageName, string $version = 'latest'): bool
    {
        $extra = $this->getExtra($packageName);
        if (!$extra) {
            throw new PackageNotFoundException($packageName);
        }

        $success = $this->packageManager->install($packageName, $version);
        
        if (!$success) {
            throw new InstallationException($packageName, 'Composer install failed');
        }

        return $success;
    }

    /**
     * @param string $packageName
     * @return bool
     */
    public function removeExtra(string $packageName): bool
    {
        return $this->packageManager->remove($packageName);
    }

    /**
     * @param string $packageName
     * @param string $version
     * @return bool
     */
    public function updateExtra(string $packageName, string $version = 'latest'): bool
    {
        return $this->packageManager->update($packageName, $version);
    }

    /**
     * @return array
     */
    public function getInstalledExtras(): array
    {
        return $this->packageManager->getInstalled();
    }

    private function ensureCacheDirectory(): void
    {
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }


}
