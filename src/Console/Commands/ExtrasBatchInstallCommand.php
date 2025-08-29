<?php

namespace hkyss\Extras\Console\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use hkyss\Extras\Enums\CommandOptions;
use hkyss\Extras\Traits\LegacyOptionsTrait;

class ExtrasBatchInstallCommand extends BaseBatchCommand
{
    protected static $defaultName = 'extras:batch:install';
    protected static $defaultDescription = 'Install multiple extras in batch mode';

    use LegacyOptionsTrait;

    protected function configure(): void
    {
        $this
            ->addArgument('packages', InputArgument::IS_ARRAY, 'List of packages to install')
            ->configureBatchOptions()

            ->addOption(CommandOptions::INSTALL_FILE->value, null, InputOption::VALUE_REQUIRED, 'File containing package list (one per line) (legacy)')
            ->addOption(CommandOptions::BATCH_INSTALL_FORCE->value, null, InputOption::VALUE_NONE, 'Skip confirmation prompts (legacy)')
            ->addOption(CommandOptions::BATCH_INSTALL_CONTINUE_ON_ERROR->value, null, InputOption::VALUE_NONE, 'Continue installation even if some packages fail (legacy)')
            ->addOption(CommandOptions::BATCH_INSTALL_DRY_RUN->value, null, InputOption::VALUE_NONE, 'Show what would be installed without actually installing (legacy)')
            ->addOption(CommandOptions::BATCH_INSTALL_PARALLEL->value, null, InputOption::VALUE_OPTIONAL, 'Number of parallel installations (default: 1) (legacy)', '1');
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

        $this->showLegacyOptionWarnings($input);
        
        if (!$this->validateNoConflictingOptions($input)) {
            $output->writeln("<error>Conflicting options detected. Please use either modern or legacy options, not both.</error>");
            return Command::FAILURE;
        }

        $dryRun = $this->hasOptionWithLegacySupport($input, CommandOptions::DRY_RUN);
        $force = $this->hasOptionWithLegacySupport($input, CommandOptions::FORCE);
        $continueOnError = $this->hasOptionWithLegacySupport($input, CommandOptions::CONTINUE_ON_ERROR);
        $parallel = (int) $this->getOptionWithLegacySupport($input, CommandOptions::PARALLEL, '1');

        if ($dryRun) {
            $this->performDryRun($output, $packages, 'install');
            return Command::SUCCESS;
        }

        if (!$force) {
            if (!$this->confirmBatchOperation($input, $output, $packages, 'install')) {
                $output->writeln('<comment>Installation cancelled.</comment>');
                return Command::SUCCESS;
            }
        }

        return $this->performBatchInstallation($output, $packages, $continueOnError, $parallel);
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

        $results = $this->processPackages(
            $packages,
            fn($package) => $this->extrasService->installExtra($package),
            $output,
            $parallel,
            $continueOnError
        );

        $this->displayBatchResults($results, $output, 'install');

        return count($results['errors']) > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
