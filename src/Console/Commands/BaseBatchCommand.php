<?php

namespace hkyss\Extras\Console\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Question\ConfirmationQuestion;

abstract class BaseBatchCommand extends BaseExtrasCommand
{
    protected function configureBatchOptions(): void
    {
        $this
            ->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'File containing package list (one per line)')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Skip confirmation prompts')
            ->addOption('continue-on-error', null, InputOption::VALUE_NONE, 'Continue operation even if some packages fail')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be done without actually doing it')
            ->addOption('parallel', 'p', InputOption::VALUE_OPTIONAL, 'Number of parallel operations (default: 1)', '1');
    }

    /**
     * @param InputInterface $input
     * @return array
     */
    protected function getPackagesList(InputInterface $input): array
    {
        $packages = $input->getArgument('packages') ?? [];
        $file = $input->getOption('file');

        if ($file && file_exists($file)) {
            $filePackages = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $packages = array_merge($packages, $filePackages);
        }

        return array_unique(array_filter($packages));
    }

    /**
     * @param array $packages
     * @param OutputInterface $output
     * @return bool
     */
    protected function validatePackagesList(array $packages, OutputInterface $output): bool
    {
        if (empty($packages)) {
            $output->writeln('<error>No packages specified for operation.</error>');
            return false;
        }

        $invalidPackages = [];
        foreach ($packages as $package) {
            if (!$this->validatePackageName($package)) {
                $invalidPackages[] = $package;
            }
        }

        if (!empty($invalidPackages)) {
            $output->writeln('<error>Invalid package names:</error>');
            foreach ($invalidPackages as $package) {
                $output->writeln("  - {$package}");
            }
            $output->writeln('<comment>Package names should be in format: vendor/package</comment>');
            return false;
        }

        return true;
    }

    /**
     * @param OutputInterface $output
     * @param array $packages
     * @param string $operation
     * @return int
     */
    protected function performDryRun(OutputInterface $output, array $packages, string $operation): int
    {
        $output->writeln("<info>DRY RUN - No packages will be {$operation}d</info>");
        $output->writeln('');

        $table = new Table($output);
        $table->setHeaders(['Package', 'Status', 'Current Version', 'Available Version']);

        foreach ($packages as $package) {
            $extra = $this->extrasService->getExtra($package);
            if ($extra) {
                $status = $extra->isInstalled() ? 'Installed' : 'Available';
                $currentVersion = 'N/A';
                
                if ($extra->isInstalled()) {
                    $installed = $this->extrasService->getInstalledExtras();
                    $currentVersion = $installed[$package] ?? 'Unknown';
                }
                
                $table->addRow([
                    $extra->getDisplayName(),
                    $status,
                    $currentVersion,
                    $extra->version
                ]);
            } else {
                $table->addRow([
                    $package,
                    '<error>Not Found</error>',
                    'N/A',
                    'N/A'
                ]);
            }
        }

        $table->render();
        return Command::SUCCESS;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param array $packages
     * @param string $operation
     * @return bool
     */
    protected function confirmBatchOperation(InputInterface $input, OutputInterface $output, array $packages, string $operation): bool
    {
        $force = $input->getOption('force');
        
        if ($force) {
            return true;
        }

        $output->writeln('');
        $output->writeln("<info>About to {$operation} " . count($packages) . " packages:</info>");
        
        foreach ($packages as $package) {
            $output->writeln("  - {$package}");
        }
        
        $output->writeln('');

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            "Do you want to continue with the {$operation} operation? (y/N): ",
            false
        );

        return $helper->ask($input, $output, $question);
    }

    /**
     * @param array $packages
     * @param callable $processor
     * @param OutputInterface $output
     * @param int $parallel
     * @param bool $continueOnError
     * @return array
     */
    protected function processPackages(
        array $packages,
        callable $processor,
        OutputInterface $output,
        int $parallel = 1,
        bool $continueOnError = false
    ): array {
        $results = [];
        $errors = [];

        if ($parallel > 1 && count($packages) > 1) {
            $output->writeln("<info>Processing packages in parallel (max: {$parallel})...</info>");
            
            foreach ($packages as $package) {
                try {
                    $results[$package] = $processor($package);
                    $output->writeln("  ✓ {$package}");
                } catch (\Exception $e) {
                    $errors[$package] = $e->getMessage();
                    $output->writeln("  ✗ {$package}: {$e->getMessage()}");
                    
                    if (!$continueOnError) {
                        throw $e;
                    }
                }
            }
        } else {
            foreach ($packages as $package) {
                try {
                    $results[$package] = $processor($package);
                    $output->writeln("  ✓ {$package}");
                } catch (\Exception $e) {
                    $errors[$package] = $e->getMessage();
                    $output->writeln("  ✗ {$package}: {$e->getMessage()}");
                    
                    if (!$continueOnError) {
                        throw $e;
                    }
                }
            }
        }

        return [
            'success' => $results,
            'errors' => $errors
        ];
    }

    /**
     * @param array $results
     * @param OutputInterface $output
     * @param string $operation
     */
    protected function displayBatchResults(array $results, OutputInterface $output, string $operation): void
    {
        $successCount = count($results['success']);
        $errorCount = count($results['errors']);
        $totalCount = $successCount + $errorCount;

        $output->writeln('');
        $output->writeln("<info>Batch {$operation} completed</info>");
        $output->writeln("  Total packages: {$totalCount}");
        $output->writeln("  Successful: <info>{$successCount}</info>");
        
        if ($errorCount > 0) {
            $output->writeln("  Failed: <error>{$errorCount}</error>");
            
            if ($output->isVerbose()) {
                $output->writeln('');
                $output->writeln('<comment>Failed packages:</comment>');
                foreach ($results['errors'] as $package => $error) {
                    $output->writeln("  - {$package}: {$error}");
                }
            }
        }
    }
}
