<?php

namespace EvolutionCMS\Extras\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use EvolutionCMS\Extras\Services\ExtrasService;

class ExtrasRemoveCommand extends Command
{
    protected static $defaultName = 'extras:remove';
    protected static $defaultDescription = 'Remove EvolutionCMS extra';

    private ExtrasService $extrasService;

    public function __construct(ExtrasService $extrasService)
    {
        parent::__construct();
        $this->extrasService = $extrasService;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('package', InputArgument::REQUIRED, 'Package name to remove')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force removal without confirmation')
            ->addOption('keep-deps', null, InputOption::VALUE_NONE, 'Keep dependencies if not used by other packages');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $packageName = $input->getArgument('package');
        $force = $input->getOption('force');

        try {
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
            $output->writeln("Description: <info>{$extra->description}</info>");
            $output->writeln("Author: <info>{$extra->author}</info>");

            if (!$force) {
                $output->writeln("\n<comment>Are you sure you want to remove this package? (y/N)</comment>");
                $handle = fopen("php://stdin", "r");
                $line = fgets($handle);
                fclose($handle);
                
                if (trim(strtolower($line)) !== 'y' && trim(strtolower($line)) !== 'yes') {
                    $output->writeln("<info>Operation cancelled</info>");
                    return Command::SUCCESS;
                }
            }

            $output->writeln("\n<info>Removing package...</info>");

            $progressBar = new ProgressBar($output, 2);
            $progressBar->start();

            $progressBar->advance();
            $progressBar->setMessage('Removing from composer.json...');

            $success = $this->extrasService->removeExtra($packageName);

            if ($success) {
                $progressBar->advance();
                $progressBar->setMessage('Package removed successfully!');
                $progressBar->finish();

                $output->writeln("\n<info>Package '{$packageName}' removed successfully!</info>");
                return Command::SUCCESS;
            } else {
                $progressBar->finish();
                $output->writeln("\n<error>Failed to remove package '{$packageName}'</error>");
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $output->writeln("<error>Error: " . $e->getMessage() . "</error>");
            return Command::FAILURE;
        }
    }
}
