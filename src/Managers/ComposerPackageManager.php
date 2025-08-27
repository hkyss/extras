<?php

namespace EvolutionCMS\Extras\Managers;

use Symfony\Component\Process\Process;
use EvolutionCMS\Extras\Interfaces\PackageManagerInterface;

class ComposerPackageManager implements PackageManagerInterface
{
    private string $projectPath;

    public function __construct(string $projectPath = null)
    {
        $this->projectPath = $projectPath ?? config('extras.composer.project_path', defined('EVO_CORE_PATH') ? EVO_CORE_PATH . '../' : '../');
    }

    /**
     * @param string $packageName
     * @param string $version
     * @return bool
     */
    public function install(string $packageName, string $version = 'latest'): bool
    {
        $composerJson = $this->getComposerJsonPath();
        $composerData = $this->readComposerJson($composerJson);
        
        $composerData['require'][$packageName] = $version === 'latest' ? '*' : $version;
        
        $this->writeComposerJson($composerJson, $composerData);
        
        return $this->runComposerInstall();
    }

    /**
     * @param string $packageName
     * @return bool
     */
    public function remove(string $packageName): bool
    {
        $composerJson = $this->getComposerJsonPath();
        $composerData = $this->readComposerJson($composerJson);
        
        if (isset($composerData['require'][$packageName])) {
            unset($composerData['require'][$packageName]);
            $this->writeComposerJson($composerJson, $composerData);
            return $this->runComposerInstall();
        }
        
        return false;
    }

    /**
     * @param string $packageName
     * @param string $version
     * @return bool
     */
    public function update(string $packageName, string $version = 'latest'): bool
    {
        return $this->install($packageName, $version);
    }

    /**
     * @return array
     */
    public function getInstalled(): array
    {
        $composerJson = $this->getComposerJsonPath();
        $composerData = $this->readComposerJson($composerJson);
        
        return $composerData['require'] ?? [];
    }

    /**
     * @param string $packageName
     * @return bool
     */
    public function isInstalled(string $packageName): bool
    {
        $installed = $this->getInstalled();
        return isset($installed[$packageName]);
    }

    private function getComposerJsonPath(): string
    {
        return $this->projectPath . 'composer.json';
    }

    /**
     * @param string $path
     * @return array
     */
    private function readComposerJson(string $path): array
    {
        if (!file_exists($path)) {
            return ['require' => []];
        }
        
        return json_decode(file_get_contents($path), true) ?: ['require' => []];
    }

    /**
     * @param string $path
     * @param array $data
     * @return void
     */
    private function writeComposerJson(string $path, array $data): void
    {
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function runComposerInstall(): bool
    {
        $process = new Process(['composer', 'install'], $this->projectPath);
        $process->setTimeout(config('extras.composer.timeout', 300));
        
        return $process->run() === 0;
    }
}
