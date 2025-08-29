<?php

namespace hkyss\Extras\Factories;

use hkyss\Extras\Services\ExtrasService;
use hkyss\Extras\Console\Commands\ListCommand;
use hkyss\Extras\Console\Commands\InstallCommand;
use hkyss\Extras\Console\Commands\RemoveCommand;
use hkyss\Extras\Console\Commands\UpdateCommand;

class CommandFactory
{
    private ExtrasService $extrasService;

    public function __construct(ExtrasService $extrasService)
    {
        $this->extrasService = $extrasService;
    }

    /**
     * @return ListCommand
     */
    public function createListCommand(): ListCommand
    {
        return new ListCommand($this->extrasService);
    }

    /**
     * @return InstallCommand
     */
    public function createInstallCommand(): InstallCommand
    {
        return new InstallCommand($this->extrasService);
    }

    /**
     * @return RemoveCommand
     */
    public function createRemoveCommand(): RemoveCommand
    {
        return new RemoveCommand($this->extrasService);
    }

    /**
     * @return UpdateCommand
     */
    public function createUpdateCommand(): UpdateCommand
    {
        return new UpdateCommand($this->extrasService);
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
