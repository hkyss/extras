<?php

namespace EvolutionCMS\Extras\Factories;

use EvolutionCMS\Extras\Services\ExtrasService;
use EvolutionCMS\Extras\Console\Commands\ExtrasListCommand;
use EvolutionCMS\Extras\Console\Commands\ExtrasInstallCommand;
use EvolutionCMS\Extras\Console\Commands\ExtrasRemoveCommand;
use EvolutionCMS\Extras\Console\Commands\ExtrasUpdateCommand;

class CommandFactory
{
    private ExtrasService $extrasService;

    public function __construct(ExtrasService $extrasService)
    {
        $this->extrasService = $extrasService;
    }

    /**
     * @return ExtrasListCommand
     */
    public function createListCommand(): ExtrasListCommand
    {
        return new ExtrasListCommand($this->extrasService);
    }

    /**
     * @return ExtrasInstallCommand
     */
    public function createInstallCommand(): ExtrasInstallCommand
    {
        return new ExtrasInstallCommand($this->extrasService);
    }

    /**
     * @return ExtrasRemoveCommand
     */
    public function createRemoveCommand(): ExtrasRemoveCommand
    {
        return new ExtrasRemoveCommand($this->extrasService);
    }

    /**
     * @return ExtrasUpdateCommand
     */
    public function createUpdateCommand(): ExtrasUpdateCommand
    {
        return new ExtrasUpdateCommand($this->extrasService);
    }

    /**
     * @return array
     */
    public function getAllCommands(): array
    {
        return [
            $this->createListCommand(),
            $this->createInstallCommand(),
            $this->createRemoveCommand(),
            $this->createUpdateCommand(),
        ];
    }
}
