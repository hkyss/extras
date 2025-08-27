<?php

namespace EvolutionCMS\Extras\Tests\Unit;

use PHPUnit\Framework\TestCase;
use EvolutionCMS\Extras\Services\ExtrasService;
use EvolutionCMS\Extras\Interfaces\ExtrasRepositoryInterface;
use EvolutionCMS\Extras\Interfaces\PackageManagerInterface;
use EvolutionCMS\Extras\Models\Extras;
use EvolutionCMS\Extras\Exceptions\PackageNotFoundException;

class ExtrasServiceTest extends TestCase
{
    private ExtrasService $service;
    private ExtrasRepositoryInterface $repository;
    private PackageManagerInterface $packageManager;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ExtrasRepositoryInterface::class);
        $this->packageManager = $this->createMock(PackageManagerInterface::class);
        $this->service = new ExtrasService($this->repository, $this->packageManager);
    }

    public function testGetAvailableExtras(): void
    {
        $extras = [
            new Extras(['name' => 'test/package1', 'description' => 'Test package 1']),
            new Extras(['name' => 'test/package2', 'description' => 'Test package 2']),
        ];

        $this->repository->expects($this->once())
            ->method('getAll')
            ->willReturn($extras);

        $result = $this->service->getAvailableExtras();

        $this->assertCount(2, $result);
        $this->assertEquals('test/package1', $result[0]->name);
        $this->assertEquals('test/package2', $result[1]->name);
    }

    public function testGetExtra(): void
    {
        $extra = new Extras(['name' => 'test/package', 'description' => 'Test package']);

        $this->repository->expects($this->once())
            ->method('find')
            ->with('test/package')
            ->willReturn($extra);

        $result = $this->service->getExtra('test/package');

        $this->assertEquals($extra, $result);
    }

    public function testInstallExtraSuccess(): void
    {
        $extra = new Extras(['name' => 'test/package', 'description' => 'Test package']);

        $this->repository->expects($this->once())
            ->method('find')
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
        $this->repository->expects($this->once())
            ->method('find')
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
