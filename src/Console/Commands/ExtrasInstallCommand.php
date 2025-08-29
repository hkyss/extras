<?php

namespace hkyss\Extras\Console\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use hkyss\Extras\Enums\CommandOptions;

class ExtrasInstallCommand extends BaseExtrasCommand
{
    protected static $defaultName = 'extras:install';
    protected static $defaultDescription = 'Install EvolutionCMS extra';

    protected function configure(): void
    {
        $this
            ->addArgument('package', InputArgument::REQUIRED, 'Package name to install')
            ->addOption(CommandOptions::VERSION->value, null, InputOption::VALUE_REQUIRED, 'Version to install', 'latest')
            ->addOption(CommandOptions::FORCE->value, null, InputOption::VALUE_NONE, 'Force installation even if already installed')
            ->addOption(CommandOptions::NO_DEPS->value, null, InputOption::VALUE_NONE, 'Skip dependency installation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $packageName = $input->getArgument('package');
        
        $version = $input->getOption(CommandOptions::VERSION->value) ?: 'latest';
        $force = $input->getOption(CommandOptions::FORCE->value);
        $noDeps = $input->getOption(CommandOptions::NO_DEPS->value);

        try {
            if (!$this->validatePackageName($packageName)) {
                $output->writeln("<error>Invalid package name format: {$packageName}</error>");
                $output->writeln("<comment>Package name should be in format: vendor/package</comment>");
                return Command::FAILURE;
            }
            
            if (!$this->validateVersion($version)) {
                $output->writeln("<error>Invalid version format: {$version}</error>");
                $output->writeln("<comment>Version should be in format: x.y.z or 'latest'</comment>");
                return Command::FAILURE;
            }

            $output->writeln("<info>Installing {$packageName}...</info>");

            $extra = $this->getValidatedPackage($packageName, $output);
            if (!$extra) {
                return Command::FAILURE;
            }

            if ($extra->isInstalled() && !$force) {
                $output->writeln("<comment>Package '{$packageName}' is already installed. Use --force to reinstall.</comment>");
                return Command::SUCCESS;
            }

            $this->displayPackageInfo($extra, $output, $version);

            if (!empty($extra->require)) {
                $output->writeln("\n<comment>Dependencies:</comment>");
                foreach ($extra->require as $dep => $depVersion) {
                    $output->writeln("  - {$dep}: {$depVersion}");
                }
            }

            $output->writeln("\n<info>Installing package...</info>");

            $this->logOperation('install_started', [
                'package' => $packageName,
                'version' => $version,
                'force' => $force,
                'no_deps' => $noDeps
            ]);

            $progressBar = $this->createProgressBar($output, 3);
            $progressBar->start();

            $progressBar->advance();
            $progressBar->setMessage('Adding to composer.json...');

            $success = $this->extrasService->installExtra($packageName, $version);

            $progressBar->advance();
            $progressBar->setMessage('Running composer install...');

            if ($success) {
                $progressBar->advance();
                $progressBar->setMessage('Installation completed!');
                $progressBar->finish();

                $output->writeln("\n<info>Package '{$packageName}' installed successfully!</info>");
                
                $this->logOperation('install_completed', [
                    'package' => $packageName,
                    'version' => $version
                ]);
                
                if (!empty($extra->extra['evolutioncms']['instructions'] ?? '')) {
                    $output->writeln("\n<comment>Installation instructions:</comment>");
                    $output->writeln($extra->extra['evolutioncms']['instructions']);
                }

                return Command::SUCCESS;
            } else {
                $progressBar->finish();
                $output->writeln("\n<error>Failed to install package '{$packageName}'</error>");
                
                $this->logOperation('install_failed', [
                    'package' => $packageName,
                    'version' => $version
                ]);
                
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            return $this->handleException($e, $output, 'install');
        }
    }
}
