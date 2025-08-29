<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use EvolutionCMS\Extras\Managers\RepositoryManager;
use EvolutionCMS\Extras\Models\Extras;
use EvolutionCMS\Extras\Interfaces\RepositoryInterface;

class RepositoryManagerTest extends TestCase
{
    private RepositoryManager $repositoryManager;

    protected function setUp(): void
    {
        $this->repositoryManager = new RepositoryManager();
    }

    public function testGetAllExtrasRemovesDuplicates(): void
    {
        $mockRepository1 = $this->createMock(RepositoryInterface::class);
        $mockRepository2 = $this->createMock(RepositoryInterface::class);

        $extra1 = new Extras([
            'name' => 'test-package-1',
            'description' => 'Test Package 1',
            'version' => '1.0.0',
            'author' => 'Test Author'
        ]);

        $extra2 = new Extras([
            'name' => 'test-package-2',
            'description' => 'Test Package 2',
            'version' => '1.0.0',
            'author' => 'Test Author'
        ]);

        $extra1Duplicate = new Extras([
            'name' => 'test-package-1',
            'description' => 'Test Package 1 Duplicate',
            'version' => '2.0.0',
            'author' => 'Test Author 2'
        ]);

        $mockRepository1->method('getName')->willReturn('Repository 1');
        $mockRepository1->method('getAll')->willReturn([$extra1, $extra2]);

        $mockRepository2->method('getName')->willReturn('Repository 2');
        $mockRepository2->method('getAll')->willReturn([$extra1Duplicate]);

        $this->repositoryManager->addRepository($mockRepository1);
        $this->repositoryManager->addRepository($mockRepository2);

        $allExtras = $this->repositoryManager->getAllExtras();

        $this->assertCount(2, $allExtras);
        
        $packageNames = array_map(fn($extra) => $extra->name, $allExtras);
        $this->assertContains('test-package-1', $packageNames);
        $this->assertContains('test-package-2', $packageNames);
        
        $this->assertCount(2, array_unique($packageNames));
    }

    public function testSearchExtrasRemovesDuplicates(): void
    {
        $mockRepository1 = $this->createMock(RepositoryInterface::class);
        $mockRepository2 = $this->createMock(RepositoryInterface::class);

        $extra1 = new Extras([
            'name' => 'search-package-1',
            'description' => 'Search Package 1',
            'version' => '1.0.0',
            'author' => 'Test Author'
        ]);

        $extra1Duplicate = new Extras([
            'name' => 'search-package-1',
            'description' => 'Search Package 1 Duplicate',
            'version' => '2.0.0',
            'author' => 'Test Author 2'
        ]);

        $mockRepository1->method('getName')->willReturn('Repository 1');
        $mockRepository1->method('search')->willReturn([$extra1]);

        $mockRepository2->method('getName')->willReturn('Repository 2');
        $mockRepository2->method('search')->willReturn([$extra1Duplicate]);

        $this->repositoryManager->addRepository($mockRepository1);
        $this->repositoryManager->addRepository($mockRepository2);

        $searchResults = $this->repositoryManager->searchExtras('search');

        $this->assertCount(1, $searchResults);
        $this->assertEquals('search-package-1', $searchResults[0]->name);
    }

    public function testGetUniqueRepositories(): void
    {
        $mockRepository1 = $this->createMock(RepositoryInterface::class);
        $mockRepository2 = $this->createMock(RepositoryInterface::class);
        $mockRepository3 = $this->createMock(RepositoryInterface::class);

        $mockRepository1->method('getName')->willReturn('Repository 1');
        $mockRepository2->method('getName')->willReturn('Repository 1'); // Дубликат
        $mockRepository3->method('getName')->willReturn('Repository 2');

        $this->repositoryManager->addRepository($mockRepository1);
        $this->repositoryManager->addRepository($mockRepository2);
        $this->repositoryManager->addRepository($mockRepository3);

        $uniqueRepositories = $this->repositoryManager->getUniqueRepositories();

        $this->assertCount(2, $uniqueRepositories);
        
        $repositoryNames = array_map(fn($repo) => $repo->getName(), $uniqueRepositories);
        $this->assertContains('Repository 1', $repositoryNames);
        $this->assertContains('Repository 2', $repositoryNames);
    }
}

