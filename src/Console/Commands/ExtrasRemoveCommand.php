<?php

namespace hkyss\Extras\Console\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use hkyss\Extras\Enums\CommandOptions;

class ExtrasRemoveCommand extends BaseExtrasCommand
{
    protected static $defaultName = 'extras:remove';
    protected static $defaultDescription = 'Remove EvolutionCMS extra';

    protected function configure(): void
    {
        $this
            ->addArgument('package', InputArgument::REQUIRED, 'Package name to remove')
            ->addOption(CommandOptions::FORCE->value, null, InputOption::VALUE_NONE, 'Force removal without confirmation')
            ->addOption(CommandOptions::KEEP_DEPS->value, null, InputOption::VALUE_NONE, 'Keep dependencies if not used by other packages');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $packageName = $input->getArgument('package');
        
        $force = $input->getOption(CommandOptions::FORCE->value);
        $keepDeps = $input->getOption(CommandOptions::KEEP_DEPS->value);

        try {
            if (!$this->validatePackageName($packageName)) {
                $output->writeln("<error>Invalid package name format: {$packageName}</error>");
                $output->writeln("<comment>Package name should be in format: vendor/package</comment>");
                return Command::FAILURE;
            }

            $extra = $this->getValidatedPackage($packageName, $output);
            if (!$extra) {
                return Command::FAILURE;
            }

            if (!$extra->isInstalled()) {
                $output->writeln("<comment>Package '{$packageName}' is not installed</comment>");
                return Command::SUCCESS;
            }

            $this->displayPackageInfo($extra, $output);

            if (!$force) {
                if (!$this->confirmOperation($input, $output, 'Are you sure you want to remove this package?')) {
                    $output->writeln("<info>Operation cancelled</info>");
                    return Command::SUCCESS;
                }
            }

            $output->writeln("\n<info>Removing package...</info>");

            $this->logOperation('remove_started', [
                'package' => $packageName,
                'force' => $force,
                'keep_deps' => $keepDeps
            ]);

            $progressBar = $this->createProgressBar($output, 2);
            $progressBar->start();

            $progressBar->advance();
            $progressBar->setMessage('Removing from composer.json...');

            $success = $this->extrasService->removeExtra($packageName);

            if ($success) {
                $progressBar->advance();
                $progressBar->setMessage('Package removed successfully!');
                $progressBar->finish();

                $output->writeln("\n<info>Package '{$packageName}' removed successfully!</info>");
                
                $this->logOperation('remove_completed', [
                    'package' => $packageName
                ]);
                
                return Command::SUCCESS;
            } else {
                $progressBar->finish();
                $output->writeln("\n<error>Failed to remove package '{$packageName}'</error>");
                
                $this->logOperation('remove_failed', [
                    'package' => $packageName
                ]);
                
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            return $this->handleException($e, $output, 'remove');
        }
    }
}
