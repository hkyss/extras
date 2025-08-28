<?php

namespace EvolutionCMS\Extras\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use EvolutionCMS\Extras\Services\ExtrasService;

class ExtrasInstallCommand extends Command
{
    protected static $defaultName = 'extras:install';
    protected static $defaultDescription = 'Install EvolutionCMS extra';

    private ExtrasService $extrasService;

    public function __construct(ExtrasService $extrasService)
    {
        parent::__construct();
        $this->extrasService = $extrasService;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('package', InputArgument::REQUIRED, 'Package name to install')
            ->addOption('install-version', null, InputOption::VALUE_REQUIRED, 'Version to install', 'latest')
            ->addOption('install-force', null, InputOption::VALUE_NONE, 'Force installation even if already installed')
            ->addOption('no-deps', null, InputOption::VALUE_NONE, 'Skip dependency installation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $packageName = $input->getArgument('package');
        $version = $input->getOption('install-version');
        $force = $input->getOption('install-force');

        try {
            $output->writeln("<info>Installing {$packageName}...</info>");

            $extra = $this->extrasService->getExtra($packageName);
            if (!$extra) {
                $output->writeln("<error>Package '{$packageName}' not found in extras store</error>");
                return Command::FAILURE;
            }

            if ($extra->isInstalled() && !$force) {
                $output->writeln("<comment>Package '{$packageName}' is already installed. Use --install-force to reinstall.</comment>");
                return Command::SUCCESS;
            }

            $output->writeln("Package: <info>{$extra->name}</info>");
            $output->writeln("Version: <info>{$version}</info>");
            $output->writeln("Description: <info>{$extra->description}</info>");
            $output->writeln("Author: <info>{$extra->author}</info>");

            if (!empty($extra->require)) {
                $output->writeln("\n<comment>Dependencies:</comment>");
                foreach ($extra->require as $dep => $depVersion) {
                    $output->writeln("  - {$dep}: {$depVersion}");
                }
            }

            $output->writeln("\n<info>Installing package...</info>");

            $progressBar = new ProgressBar($output, 3);
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
                
                if (!empty($extra->extra['evolutioncms']['instructions'] ?? '')) {
                    $output->writeln("\n<comment>Installation instructions:</comment>");
                    $output->writeln($extra->extra['evolutioncms']['instructions']);
                }

                return Command::SUCCESS;
            } else {
                $progressBar->finish();
                $output->writeln("\n<error>Failed to install package '{$packageName}'</error>");
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $output->writeln("<error>Error: " . $e->getMessage() . "</error>");
            return Command::FAILURE;
        }
    }
}
