<?php

namespace EvolutionCMS\Extras\Services;

use EvolutionCMS\Extras\Models\Extras;
use EvolutionCMS\Extras\Interfaces\ExtrasRepositoryInterface;
use EvolutionCMS\Extras\Interfaces\PackageManagerInterface;
use EvolutionCMS\Extras\Services\RepositoryManager;
use EvolutionCMS\Extras\Exceptions\PackageNotFoundException;
use EvolutionCMS\Extras\Exceptions\InstallationException;

class ExtrasService
{
    private RepositoryManager $repositoryManager;
    private PackageManagerInterface $packageManager;
    private string $cachePath;

    public function __construct(RepositoryManager $repositoryManager, PackageManagerInterface $packageManager = null)
    {
        $this->repositoryManager = $repositoryManager;
        $this->packageManager = $packageManager ?? new \EvolutionCMS\Extras\Managers\ComposerPackageManager();
        $this->cachePath = config('extras.cache.path', EVO_CORE_PATH . 'cache/extras/');
        $this->ensureCacheDirectory();
    }

    /**
     * @return Extras[]
     */
    public function getAvailableExtras(): array
    {
        return $this->repositoryManager->getAllExtras();
    }

    /**
     * @param string $packageName
     * @return Extras|null
     */
    public function getExtra(string $packageName): ?Extras
    {
        return $this->repositoryManager->findExtra($packageName);
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
