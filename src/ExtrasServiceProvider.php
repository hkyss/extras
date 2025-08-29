<?php

namespace hkyss\Extras;

use Illuminate\Support\ServiceProvider;
use hkyss\Extras\Console\Commands\ListCommand;
use hkyss\Extras\Console\Commands\InstallCommand;
use hkyss\Extras\Console\Commands\RemoveCommand;
use hkyss\Extras\Console\Commands\UpdateCommand;
use hkyss\Extras\Console\Commands\BatchInstallCommand;
use hkyss\Extras\Console\Commands\BatchUpdateCommand;
use hkyss\Extras\Console\Commands\BatchRemoveCommand;
use hkyss\Extras\Console\Commands\InfoCommand;
use hkyss\Extras\Console\Commands\CacheCommand;
use hkyss\Extras\Services\ExtrasService;
use hkyss\Extras\Services\CacheService;
use hkyss\Extras\Managers\RepositoryManager;
use hkyss\Extras\Interfaces\PackageManagerInterface;
use hkyss\Extras\Repositories\ApiRepository;
use hkyss\Extras\Repositories\GitHubRepository;
use hkyss\Extras\Managers\ComposerPackageManager;

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
            
            $manager->addRepository(new ApiRepository(null, $cacheService));
            
            $repositories = config('extras.repositories', []);
            $addedOrganizations = [];
            
            foreach ($repositories as $repo) {
                if (isset($repo['type']) && $repo['type'] === 'github') {
                    $organization = $repo['organization'];
                    
                    if (!in_array($organization, $addedOrganizations)) {
                        $manager->addRepository(new GitHubRepository(
                            $organization,
                            $repo['name'] ?? 'GitHub',
                            $cacheService
                        ));
                        $addedOrganizations[] = $organization;
                    }
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
            ListCommand::class,
            InstallCommand::class,
            RemoveCommand::class,
            UpdateCommand::class,
            BatchInstallCommand::class,
            BatchUpdateCommand::class,
            BatchRemoveCommand::class,
            InfoCommand::class,
            CacheCommand::class,
        ]);

        $this->publishes([
            __DIR__ . '/../config/extras.php' => config_path('extras.php'),
        ], 'extras-config');
    }
}
