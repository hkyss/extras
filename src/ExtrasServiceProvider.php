<?php

namespace EvolutionCMS\Extras;

use Illuminate\Support\ServiceProvider;
use EvolutionCMS\Extras\Console\Commands\ExtrasListCommand;
use EvolutionCMS\Extras\Console\Commands\ExtrasInstallCommand;
use EvolutionCMS\Extras\Console\Commands\ExtrasRemoveCommand;
use EvolutionCMS\Extras\Console\Commands\ExtrasUpdateCommand;
use EvolutionCMS\Extras\Services\ExtrasService;
use EvolutionCMS\Extras\Interfaces\ExtrasRepositoryInterface;
use EvolutionCMS\Extras\Interfaces\PackageManagerInterface;
use EvolutionCMS\Extras\Repositories\ApiRepository;
use EvolutionCMS\Extras\Managers\ComposerPackageManager;

class ExtrasServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ExtrasRepositoryInterface::class, function ($app) {
            return new ApiRepository();
        });

        $this->app->singleton(PackageManagerInterface::class, function ($app) {
            return new ComposerPackageManager();
        });

        $this->app->singleton(ExtrasService::class, function ($app) {
            return new ExtrasService(
                $app->make(ExtrasRepositoryInterface::class),
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
        ]);

        $this->publishes([
            __DIR__ . '/../config/extras.php' => config_path('extras.php'),
        ], 'extras-config');
    }
}
