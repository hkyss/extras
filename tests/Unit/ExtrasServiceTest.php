<?php

namespace hkyss\Extras\Tests\Unit;

use PHPUnit\Framework\TestCase;
use hkyss\Extras\Services\ExtrasService;
use hkyss\Extras\Managers\RepositoryManager;
use hkyss\Extras\Interfaces\PackageManagerInterface;
use hkyss\Extras\Models\Extras;
use hkyss\Extras\Exceptions\PackageNotFoundException;

class ExtrasServiceTest extends TestCase
{
    private ExtrasService $service;
    private RepositoryManager $repositoryManager;
    private PackageManagerInterface $packageManager;

    protected function setUp(): void
    {
        $this->repositoryManager = $this->createMock(RepositoryManager::class);
        $this->packageManager = $this->createMock(PackageManagerInterface::class);
        $this->service = new ExtrasService($this->repositoryManager, $this->packageManager);
    }

    public function testGetAvailableExtras(): void
    {
        $extras = [
            new Extras(['name' => 'test/package1', 'description' => 'Test package 1']),
            new Extras(['name' => 'test/package2', 'description' => 'Test package 2']),
        ];

        $this->repositoryManager->expects($this->once())
            ->method('getAllExtras')
            ->willReturn($extras);

        $result = $this->service->getAvailableExtras();

        $this->assertCount(2, $result);
        $this->assertEquals('test/package1', $result[0]->name);
        $this->assertEquals('test/package2', $result[1]->name);
    }

    public function testGetExtra(): void
    {
        $extra = new Extras(['name' => 'test/package', 'description' => 'Test package']);

        $this->repositoryManager->expects($this->once())
            ->method('findExtra')
            ->with('test/package')
            ->willReturn($extra);

        $result = $this->service->getExtra('test/package');

        $this->assertEquals($extra, $result);
    }

    public function testInstallExtraSuccess(): void
    {
        $extra = new Extras(['name' => 'test/package', 'description' => 'Test package']);

        $this->repositoryManager->expects($this->once())
            ->method('findExtra')
            ->with('test/package')
            ->willReturn($extra);

        $this->packageManager->expects($this->once())
            ->method('install')
            ->with('test/package', 'latest')
            ->willReturn(true);

        $result = $this->service->installExtra('test/package');

        $this->assertTrue($result);
    }

    public function testInstallExtraNotFound(): void
    {
        $this->repositoryManager->expects($this->once())
            ->method('findExtra')
            ->with('test/package')
            ->willReturn(null);

        $this->expectException(PackageNotFoundException::class);

        $this->service->installExtra('test/package');
    }

    public function testGetInstalledExtras(): void
    {
        $installed = ['test/package1' => '1.0.0', 'test/package2' => '2.0.0'];

        $this->packageManager->expects($this->once())
            ->method('getInstalled')
            ->willReturn($installed);

        $result = $this->service->getInstalledExtras();

        $this->assertEquals($installed, $result);
    }
}
