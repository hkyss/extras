<?php

namespace hkyss\Extras\Console\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use hkyss\Extras\Enums\CommandOptions;

class ExtrasBatchInstallCommand extends BaseBatchCommand
{
    protected static $defaultName = 'extras:batch:install';
    protected static $defaultDescription = 'Install multiple extras in batch mode';

    protected function configure(): void
    {
        $this
            ->addArgument('packages', InputArgument::IS_ARRAY, 'List of packages to install')
            ->configureBatchOptions();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $packages = $this->getPackagesList($input);
        
        if (empty($packages)) {
            $output->writeln('<error>No packages specified for installation</error>');
            $output->writeln('Usage: php artisan extras:batch:install package1 package2 package3');
            $output->writeln('   or: php artisan extras:batch:install --file=packages.txt');
            return Command::FAILURE;
        }

                $dryRun = $input->getOption(CommandOptions::DRY_RUN->value);
        $force = $input->getOption(CommandOptions::FORCE->value);
        $continueOnError = $input->getOption(CommandOptions::CONTINUE_ON_ERROR->value);
        $parallel = (int) ($input->getOption(CommandOptions::PARALLEL->value) ?: '1');

        if ($dryRun) {
            $this->performDryRun($output, $packages, 'install');
            return Command::SUCCESS;
        }

        if (!$force) {
            if (!$this->confirmBatchOperation($input, $output, $packages, 'install')) {
                $output->writeln('<comment>Installation cancelled</comment>');
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
        $output->writeln('<info>Starting batch installation</info>');
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
