<?php

namespace EvolutionCMS\Extras\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use EvolutionCMS\Extras\Services\ExtrasService;
use EvolutionCMS\Extras\Models\Extras;

class ExtrasBatchInstallCommand extends Command
{
    protected static $defaultName = 'extras:batch:install';
    protected static $defaultDescription = 'Install multiple extras in batch mode';

    private ExtrasService $extrasService;

    public function __construct(ExtrasService $extrasService)
    {
        parent::__construct();
        $this->extrasService = $extrasService;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('packages', InputArgument::IS_ARRAY, 'List of packages to install')
            ->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'File containing package list (one per line)')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Skip confirmation prompts')
            ->addOption('continue-on-error', 'c', InputOption::VALUE_NONE, 'Continue installation even if some packages fail')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Show what would be installed without actually installing')
            ->addOption('parallel', 'p', InputOption::VALUE_OPTIONAL, 'Number of parallel installations (default: 1)', '1');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $packages = $this->getPackagesList($input);
        
        if (empty($packages)) {
            $output->writeln('<error>No packages specified for installation.</error>');
            $output->writeln('Usage: php artisan extras:batch:install package1 package2 package3');
            $output->writeln('   or: php artisan extras:batch:install --file=packages.txt');
            return Command::FAILURE;
        }

        $dryRun = $input->getOption('dry-run');
        $force = $input->getOption('force');
        $continueOnError = $input->getOption('continue-on-error');
        $parallel = (int) $input->getOption('parallel');

        if ($dryRun) {
            $this->performDryRun($output, $packages);
            return Command::SUCCESS;
        }

        if (!$force) {
            if (!$this->confirmInstallation($input, $output, $packages)) {
                $output->writeln('<comment>Installation cancelled.</comment>');
                return Command::SUCCESS;
            }
        }

        return $this->performBatchInstallation($output, $packages, $continueOnError, $parallel);
    }

    /**
     * @param InputInterface $input
     * @return array
     */
    private function getPackagesList(InputInterface $input): array
    {
        $packages = $input->getArgument('packages');
        $file = $input->getOption('file');

        if ($file && file_exists($file)) {
            $filePackages = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $packages = array_merge($packages, $filePackages);
        }

        return array_unique(array_filter($packages));
    }

    /**
     * @param OutputInterface $output
     * @param array $packages
     * @return void
     */
    private function performDryRun(OutputInterface $output, array $packages): void
    {
        $output->writeln('<info>DRY RUN - No packages will be installed</info>');
        $output->writeln('');

        $table = new \Symfony\Component\Console\Helper\Table($output);
        $table->setHeaders(['Package', 'Status', 'Current Version', 'Available Version']);

        foreach ($packages as $package) {
            try {
                $extra = $this->extrasService->getExtra($package);
                if ($extra) {
                    $status = $extra->isInstalled() ? 'Already Installed' : 'Will Install';
                    $currentVersion = $extra->isInstalled() ? 'Installed' : 'Not Installed';
                    $availableVersion = $extra->version;
                } else {
                    $status = 'Not Found';
                    $currentVersion = 'N/A';
                    $availableVersion = 'N/A';
                }
            } catch (\Exception $e) {
                $status = 'Error';
                $currentVersion = 'N/A';
                $availableVersion = 'N/A';
            }

            $table->addRow([$package, $status, $currentVersion, $availableVersion]);
        }

        $table->render();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param array $packages
     * @return bool
     */
    private function confirmInstallation(InputInterface $input, OutputInterface $output, array $packages): bool
    {
        $output->writeln('<info>Packages to install:</info>');
        foreach ($packages as $package) {
            $output->writeln("  - {$package}");
        }
        $output->writeln('');

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            'Do you want to proceed with the installation? (y/N): ',
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
    private function performBatchInstallation(OutputInterface $output, array $packages, bool $continueOnError, int $parallel): int
    {
        $output->writeln('<info>Starting batch installation...</info>');
        $output->writeln('');

        $progressBar = new ProgressBar($output, count($packages));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $progressBar->start();

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($packages as $package) {
            try {
                $this->extrasService->installExtra($package);
                $successCount++;
                $progressBar->setMessage("Installed: {$package}");
            } catch (\Exception $e) {
                $errorCount++;
                $errors[] = "{$package}: {$e->getMessage()}";
                $progressBar->setMessage("Failed: {$package}");

                if (!$continueOnError) {
                    $progressBar->finish();
                    $output->writeln('');
                    $output->writeln("<error>Installation failed for {$package}: {$e->getMessage()}</error>");
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
        $output->writeln('<info>Batch installation completed!</info>');
        $output->writeln("Successfully installed: <info>{$successCount}</info> packages");
        
        if ($errorCount > 0) {
            $output->writeln("Failed to install: <error>{$errorCount}</error> packages");
            $output->writeln('');
            $output->writeln('<comment>Errors:</comment>');
            foreach ($errors as $error) {
                $output->writeln("  - {$error}");
            }
        }
    }
}
