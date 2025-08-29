<?php

namespace hkyss\Extras\Interfaces;

interface PackageManagerInterface
{
    /**
     * @param string $packageName
     * @param string $version
     * @return bool
     */
    public function install(string $packageName, string $version = 'latest'): bool;

    /**
     * @param string $packageName
     * @return bool
     */
    public function remove(string $packageName): bool;

    /**
     * @param string $packageName
     * @param string $version
     * @return bool
     */
    public function update(string $packageName, string $version = 'latest'): bool;

    /**
     * @return array
     */
    public function getInstalled(): array;

    /**
     * @param string $packageName
     * @return bool
     */
    public function isInstalled(string $packageName): bool;
}
