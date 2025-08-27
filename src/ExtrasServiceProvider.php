<?php

namespace EvolutionCMS\Extras;

use Illuminate\Support\ServiceProvider;
use EvolutionCMS\Extras\Console\Commands\ExtrasListCommand;
use EvolutionCMS\Extras\Console\Commands\ExtrasInstallCommand;
use EvolutionCMS\Extras\Console\Commands\ExtrasRemoveCommand;
use EvolutionCMS\Extras\Console\Commands\ExtrasUpdateCommand;
use EvolutionCMS\Extras\Console\Commands\ExtrasBatchInstallCommand;
use EvolutionCMS\Extras\Console\Commands\ExtrasBatchUpdateCommand;
use EvolutionCMS\Extras\Console\Commands\ExtrasBatchRemoveCommand;
use EvolutionCMS\Extras\Services\ExtrasService;
use EvolutionCMS\Extras\Services\CacheService;
use EvolutionCMS\Extras\Managers\RepositoryManager;
use EvolutionCMS\Extras\Interfaces\PackageManagerInterface;
use EvolutionCMS\Extras\Repositories\ApiRepository;
use EvolutionCMS\Extras\Repositories\GitHubRepository;
use EvolutionCMS\Extras\Managers\ComposerPackageManager;

class ExtrasServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CacheService::class, function ($app) {
            return new CacheService($app->make('cache'));
        });

        $this->app->singleton(RepositoryManager::class, function ($app) {
            $manager = new RepositoryManager();
            $cacheService = $app->make(CacheService::class);
            
            $manager->addRepository(new ApiRepository());
            
            $manager->addRepository(new GitHubRepository('evolution-cms-extras', 'EvolutionCMS Extras', $cacheService));
            
            $repositories = config('extras.repositories', []);
            foreach ($repositories as $repo) {
                if (isset($repo['type']) && $repo['type'] === 'github') {
                    $manager->addRepository(new GitHubRepository(
                        $repo['organization'],
                        $repo['name'] ?? 'GitHub',
                        $cacheService
                    ));
                }
            }
            
            return $manager;
        });

        $this->app->singleton(PackageManagerInterface::class, function ($app) {
            return new ComposerPackageManager();
        });

        $this->app->singleton(ExtrasService::class, function ($app) {
            return new ExtrasService(
                $app->make(RepositoryManager::class),
                $app->make(PackageManagerInterface::class)
            );
        });
    }

    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/extras.php', 'extras');

        $this->commands([
            ExtrasListCommand::class,
            ExtrasInstallCommand::class,
            ExtrasRemoveCommand::class,
            ExtrasUpdateCommand::class,
            ExtrasBatchInstallCommand::class,
            ExtrasBatchUpdateCommand::class,
            ExtrasBatchRemoveCommand::class,
        ]);

        $this->publishes([
            __DIR__ . '/../config/extras.php' => config_path('extras.php'),
        ], 'extras-config');
    }
}
