<?php

namespace hkyss\Extras\Console\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class ExtrasBatchRemoveCommand extends BaseBatchCommand
{
    protected static $defaultName = 'extras:batch:remove';
    protected static $defaultDescription = 'Remove multiple extras in batch mode';

    use LegacyOptionsTrait;

    protected function configure(): void
    {
        $this
            ->addArgument('packages', InputArgument::IS_ARRAY, 'List of packages to remove')
            ->configureBatchOptions()
            ->addOption(CommandOptions::ALL->value, 'a', InputOption::VALUE_NONE, 'Remove all installed extras')
            // Legacy options for backward compatibility
            ->addOption(CommandOptions::REMOVE_FILE->value, null, InputOption::VALUE_REQUIRED, 'File containing package list (one per line) (legacy)')
            ->addOption(CommandOptions::BATCH_REMOVE_FORCE->value, null, InputOption::VALUE_NONE, 'Skip confirmation prompts (legacy)')
            ->addOption(CommandOptions::BATCH_REMOVE_CONTINUE_ON_ERROR->value, null, InputOption::VALUE_NONE, 'Continue removal even if some packages fail (legacy)')
            ->addOption(CommandOptions::BATCH_REMOVE_DRY_RUN->value, null, InputOption::VALUE_NONE, 'Show what would be removed without actually removing (legacy)')
            ->addOption(CommandOptions::BATCH_KEEP_DEPS->value, null, InputOption::VALUE_NONE, 'Keep dependencies when removing packages (legacy)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $packages = $this->getPackagesList($input);
        $dryRun = $input->getOption('batch-remove-dry-run');

        if (empty($packages)) {
            $output->writeln('<error>No packages specified for removal.</error>');
            $output->writeln('Usage: php artisan extras:batch:remove package1 package2 package3');
            $output->writeln('   or: php artisan extras:batch:remove --file=packages.txt');
            $output->writeln('   or: php artisan extras:batch:remove --all');
            return Command::FAILURE;
        }

        if ($dryRun) {
            return $this->performDryRun($output, $packages);
        }

        $force = $input->getOption('batch-remove-force');
        $continueOnError = $input->getOption('batch-remove-continue-on-error');
        $keepDeps = $input->getOption('batch-keep-deps');

        if (!$force) {
            if (!$this->confirmRemoval($input, $output, $packages)) {
                $output->writeln('<comment>Removal cancelled.</comment>');
                return Command::SUCCESS;
            }
        }

        return $this->performBatchRemoval($output, $packages, $continueOnError, $keepDeps);
    }

    /**
     * @param InputInterface $input
     * @return array
     */
    private function getPackagesList(InputInterface $input): array
    {
        $packages = $input->getArgument('packages');
        $file = $input->getOption('remove-file');
        $all = $input->getOption('all');

        if ($file && file_exists($file)) {
            $filePackages = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $packages = array_merge($packages, $filePackages);
        }

        if ($all) {
            $installed = $this->extrasService->getInstalledExtras();
            $packages = array_merge($packages, array_keys($installed));
        }

        return array_unique(array_filter($packages));
    }

    /**
     * @param OutputInterface $output
     * @param array $packages
     * @return int
     */
    private function performDryRun(OutputInterface $output, array $packages): int
    {
        $output->writeln('<info>DRY RUN - No packages will be removed</info>');
        $output->writeln('');

        $table = new \Symfony\Component\Console\Helper\Table($output);
        $table->setHeaders(['Package', 'Status', 'Current Version', 'Action']);

        foreach ($packages as $package) {
            try {
                $installed = $this->extrasService->getInstalledExtras();
                if (isset($installed[$package])) {
                    $status = 'Installed';
                    $currentVersion = $installed[$package];
                    $action = 'Will Remove';
                } else {
                    $status = 'Not Installed';
                    $currentVersion = 'N/A';
                    $action = 'Skip';
                }
            } catch (\Exception $e) {
                $status = 'Error';
                $currentVersion = 'N/A';
                $action = 'Error';
            }

            $table->addRow([$package, $status, $currentVersion, $action]);
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
    private function confirmRemoval(InputInterface $input, OutputInterface $output, array $packages): bool
    {
        $output->writeln('<warning>WARNING: This action cannot be undone!</warning>');
        $output->writeln('');
        $output->writeln('<info>Packages to remove:</info>');
        foreach ($packages as $package) {
            $output->writeln("  - {$package}");
        }
        $output->writeln('');

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            'Are you sure you want to remove these packages? (y/N): ',
            false
        );

        if (!$helper->ask($input, $output, $question)) {
            return false;
        }

        $question2 = new ConfirmationQuestion(
            'This action is irreversible. Type "yes" to confirm: ',
            false
        );

        return $helper->ask($input, $output, $question2);
    }

    /**
     * @param OutputInterface $output
     * @param array $packages
     * @param bool $continueOnError
     * @param bool $keepDeps
     * @return int
     */
    private function performBatchRemoval(OutputInterface $output, array $packages, bool $continueOnError, bool $keepDeps): int
    {
        $output->writeln('<info>Starting batch removal...</info>');
        $output->writeln('');

        $progressBar = new ProgressBar($output, count($packages));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $progressBar->start();

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($packages as $package) {
            try {
                $this->extrasService->removeExtra($package);
                $successCount++;
                $progressBar->setMessage("Removed: {$package}");
            } catch (\Exception $e) {
                $errorCount++;
                $errors[] = "{$package}: {$e->getMessage()}";
                $progressBar->setMessage("Failed: {$package}");

                if (!$continueOnError) {
                    $progressBar->finish();
                    $output->writeln('');
                    $output->writeln("<error>Removal failed for {$package}: {$e->getMessage()}</error>");
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
        $output->writeln('<info>Batch removal completed!</info>');
        $output->writeln("Successfully removed: <info>{$successCount}</info> packages");
        
        if ($errorCount > 0) {
            $output->writeln("Failed to remove: <error>{$errorCount}</error> packages");
            $output->writeln('');
            $output->writeln('<comment>Errors:</comment>');
            foreach ($errors as $error) {
                $output->writeln("  - {$error}");
            }
        }
    }
}
