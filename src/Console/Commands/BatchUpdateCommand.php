<?php

namespace hkyss\Extras\Console\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use hkyss\Extras\Enums\CommandOptions;

class BatchUpdateCommand extends AbstractBatchCommand
{
    protected static $defaultName = 'extras:batch:update';
    protected static $defaultDescription = 'Update multiple extras in batch mode';

    protected function configure(): void
    {
        $this
            ->addArgument('packages', InputArgument::IS_ARRAY, 'List of packages to update (leave empty for all installed)')
            ->configureBatchOptions();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $packages = $this->getPackagesList($input);
        $checkOnly = $input->getOption(CommandOptions::CHECK_ONLY->value);
        $dryRun = $input->getOption(CommandOptions::DRY_RUN->value);

        if ($checkOnly) {
            return $this->checkForUpdates($output, $packages);
        }

        if ($dryRun) {
            return $this->performDryRun($output, $packages);
        }

        $force = $input->getOption(CommandOptions::FORCE->value);
        $continueOnError = $input->getOption(CommandOptions::CONTINUE_ON_ERROR->value);
        $parallel = (int) ($input->getOption(CommandOptions::PARALLEL->value) ?: '1');

        if (!$force) {
            if (!$this->confirmUpdate($input, $output, $packages)) {
                $output->writeln('<comment>Update cancelled</comment>');
                return Command::SUCCESS;
            }
        }

        return $this->performBatchUpdate($output, $packages, $continueOnError, $parallel);
    }

    /**
     * @param InputInterface $input
     * @return array
     */
    private function getPackagesList(InputInterface $input): array
    {
        $packages = $input->getArgument('packages');
        $file = $input->getOption(CommandOptions::FILE->value);

        if ($file && file_exists($file)) {
            $filePackages = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $packages = array_merge($packages, $filePackages);
        }

        if (empty($packages)) {
            $installed = $this->extrasService->getInstalledExtras();
            $packages = array_keys($installed);
        }

        return array_unique(array_filter($packages));
    }

    /**
     * @param OutputInterface $output
     * @param array $packages
     * @return int
     */
    private function checkForUpdates(OutputInterface $output, array $packages): int
    {
        $output->writeln('<info>Checking for available updates</info>');
        $output->writeln('');

        $table = new \Symfony\Component\Console\Helper\Table($output);
        $table->setHeaders(['Package', 'Current Version', 'Available Version', 'Status']);

        $hasUpdates = false;

        foreach ($packages as $package) {
            try {
                $extra = $this->extrasService->getExtra($package);
                if ($extra && $extra->isInstalled()) {
                    $installed = $this->extrasService->getInstalledExtras();
                    $currentVersion = $installed[$package] ?? 'Unknown';
                    
                    if (version_compare($extra->version, $currentVersion, '>')) {
                        $status = '<info>Update Available</info>';
                        $hasUpdates = true;
                    } else {
                        $status = '<comment>Up to Date</comment>';
                    }
                    
                    $table->addRow([$package, $currentVersion, $extra->version, $status]);
                }
            } catch (\Exception $e) {
                $table->addRow([$package, 'Error', 'N/A', '<error>Error</error>']);
            }
        }

        $table->render();
        $output->writeln('');

        if ($hasUpdates) {
            $output->writeln('<info>Updates are available for some packages</info>');
            $output->writeln('Run without --check-only to perform the updates.');
        } else {
            $output->writeln('<comment>All packages are up to date</comment>');
        }

        return Command::SUCCESS;
    }

    /**
     * @param OutputInterface $output
     * @param array $packages
     * @return int
     */
    private function performDryRun(OutputInterface $output, array $packages): int
    {
        $output->writeln('<info>DRY RUN - No packages will be updated</info>');
        $output->writeln('');

        $table = new \Symfony\Component\Console\Helper\Table($output);
        $table->setHeaders(['Package', 'Current Version', 'New Version', 'Action']);

        foreach ($packages as $package) {
            try {
                $extra = $this->extrasService->getExtra($package);
                if ($extra && $extra->isInstalled()) {
                    $installed = $this->extrasService->getInstalledExtras();
                    $currentVersion = $installed[$package] ?? 'Unknown';
                    
                    if (version_compare($extra->version, $currentVersion, '>')) {
                        $action = 'Will Update';
                    } else {
                        $action = 'No Update Needed';
                    }
                    
                    $table->addRow([$package, $currentVersion, $extra->version, $action]);
                } else {
                    $table->addRow([$package, 'Not Installed', 'N/A', 'Skip']);
                }
            } catch (\Exception $e) {
                $table->addRow([$package, 'Error', 'N/A', 'Error']);
            }
        }

        $table->render();
        return Command::SUCCESS;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param array $packages
     * @return bool
     */
    private function confirmUpdate(InputInterface $input, OutputInterface $output, array $packages): bool
    {
        $output->writeln('<info>Packages to update</info>');
        foreach ($packages as $package) {
            $output->writeln("  - {$package}");
        }
        $output->writeln('');

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            'Do you want to proceed with the update? (y/N): ',
            false
        );

        return $helper->ask($input, $output, $question);
    }

    /**
     * @param OutputInterface $output
     * @param array $packages
     * @param bool $continueOnError
     * @param int $parallel
     * @return int
     */
    private function performBatchUpdate(OutputInterface $output, array $packages, bool $continueOnError, int $parallel): int
    {
        $output->writeln('<info>Starting batch update</info>');
        $output->writeln('');

        $progressBar = new ProgressBar($output, count($packages));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $progressBar->start();

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($packages as $package) {
            try {
                $this->extrasService->updateExtra($package);
                $successCount++;
                $progressBar->setMessage("Updated: {$package}");
            } catch (\Exception $e) {
                $errorCount++;
                $errors[] = "{$package}: {$e->getMessage()}";
                $progressBar->setMessage("Failed: {$package}");

                if (!$continueOnError) {
                    $progressBar->finish();
                    $output->writeln('');
                    $output->writeln("<error>Update failed for {$package}: {$e->getMessage()}</error>");
                    $output->writeln('Use --continue-on-error to continue with remaining packages.');
                    return Command::FAILURE;
                }
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $output->writeln('');
        $output->writeln('');

        $this->displayResults($output, $successCount, $errorCount, $errors);

        return $errorCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * @param OutputInterface $output
     * @param int $successCount
     * @param int $errorCount
     * @param array $errors
     * @return void
     */
    private function displayResults(OutputInterface $output, int $successCount, int $errorCount, array $errors): void
    {
                    $output->writeln('<info>Batch update completed</info>');
        $output->writeln("Successfully updated: <info>{$successCount}</info> packages");
        
        if ($errorCount > 0) {
            $output->writeln("Failed to update: <error>{$errorCount}</error> packages");
            $output->writeln('');
            $output->writeln('<comment>Errors</comment>');
            foreach ($errors as $error) {
                $output->writeln("  - {$error}");
            }
        }
    }
}
