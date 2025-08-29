<?php

namespace hkyss\Extras\Console\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use hkyss\Extras\Enums\CommandOptions;

class ExtrasUpdateCommand extends BaseExtrasCommand
{
    protected static $defaultName = 'extras:update';
    protected static $defaultDescription = 'Update EvolutionCMS extra';

    protected function configure(): void
    {
        $this
            ->addArgument('package', InputArgument::OPTIONAL, 'Package name to update (if not specified, updates all)')
            ->addOption(CommandOptions::VERSION->value, null, InputOption::VALUE_REQUIRED, 'Version to update to', 'latest')
            ->addOption(CommandOptions::FORCE->value, null, InputOption::VALUE_NONE, 'Force update even if already at latest version')
            ->addOption(CommandOptions::CHECK_ONLY->value, null, InputOption::VALUE_NONE, 'Only check for updates without installing');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $packageName = $input->getArgument('package');
        
        $version = $input->getOption(CommandOptions::VERSION->value) ?: 'latest';
        $force = $input->getOption(CommandOptions::FORCE->value);
        $checkOnly = $input->getOption(CommandOptions::CHECK_ONLY->value);

        try {
            if ($packageName) {
                return $this->updateSinglePackage($output, $packageName, $version, $force, $checkOnly);
            } else {
                return $this->updateAllPackages($output, $version, $force, $checkOnly);
            }
        } catch (\Exception $e) {
            return $this->handleException($e, $output, 'update');
        }
    }

    /**
     * @param OutputInterface $output
     * @param string $packageName
     * @param string $version
     * @param bool $force
     * @param bool $checkOnly
     * @return int
     */
    private function updateSinglePackage(OutputInterface $output, string $packageName, string $version, bool $force, bool $checkOnly): int
    {
        $extra = $this->extrasService->getExtra($packageName);
        if (!$extra) {
            $output->writeln("<error>Package '{$packageName}' not found in extras store</error>");
            return Command::FAILURE;
        }

        if (!$extra->isInstalled()) {
            $output->writeln("<comment>Package '{$packageName}' is not installed</comment>");
            return Command::SUCCESS;
        }

        $output->writeln("Package: <info>{$extra->name}</info>");
        $output->writeln("Current Version: <info>{$extra->version}</info>");
        $output->writeln("Target Version: <info>{$version}</info>");

        if ($checkOnly) {
            $output->writeln("<info>Update check completed</info>");
            return Command::SUCCESS;
        }

                    $output->writeln("\n<info>Updating package</info>");

        $progressBar = new ProgressBar($output, 3);
        $progressBar->start();

        $progressBar->advance();
                    $progressBar->setMessage('Updating composer.json');

        $success = $this->extrasService->updateExtra($packageName, $version);

        if ($success) {
            $progressBar->advance();
            $progressBar->setMessage('Running composer update');
            $progressBar->advance();
            $progressBar->setMessage('Update completed');
            $progressBar->finish();

                            $output->writeln("\n<info>Package '{$packageName}' updated successfully</info>");
            return Command::SUCCESS;
        } else {
            $progressBar->finish();
            $output->writeln("\n<error>Failed to update package '{$packageName}'</error>");
            return Command::FAILURE;
        }
    }

    /**
     * @param OutputInterface $output
     * @param string $version
     * @param bool $force
     * @param bool $checkOnly
     * @return int
     */
    private function updateAllPackages(OutputInterface $output, string $version, bool $force, bool $checkOnly): int
    {
        $installed = $this->extrasService->getInstalledExtras();
        
        if (empty($installed)) {
            $output->writeln("<comment>No installed packages found</comment>");
            return Command::SUCCESS;
        }

        $output->writeln("<info>Found " . count($installed) . " installed packages</info>");

        $updatedCount = 0;
        $failedCount = 0;

        foreach ($installed as $packageName => $currentVersion) {
            $output->writeln("\nProcessing: <info>{$packageName}</info>");

            $extra = $this->extrasService->getExtra($packageName);
            if (!$extra) {
                $output->writeln("<comment>Package '{$packageName}' not found in store, skipping</comment>");
                continue;
            }

            $output->writeln("Current: <info>{$currentVersion}</info>");
            $output->writeln("Available: <info>{$extra->version}</info>");

            if ($checkOnly) {
                if ($currentVersion !== $extra->version) {
                    $output->writeln("<comment>Update available</comment>");
                } else {
                    $output->writeln("<info>Already up to date</info>");
                }
                continue;
            }

            if ($currentVersion === $extra->version && !$force) {
                $output->writeln("<info>Already up to date</info>");
                continue;
            }

            $success = $this->extrasService->updateExtra($packageName, $version);
            
            if ($success) {
                $output->writeln("<info>Updated successfully</info>");
                $updatedCount++;
            } else {
                $output->writeln("<error>Update failed</error>");
                $failedCount++;
            }
        }

        $output->writeln("\n<info>Update summary</info>");
        $output->writeln("Updated: <info>{$updatedCount}</info>");
        $output->writeln("Failed: <error>{$failedCount}</error>");

        return $failedCount === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
