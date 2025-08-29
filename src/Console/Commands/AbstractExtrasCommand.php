<?php

namespace hkyss\Extras\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use hkyss\Extras\Services\ExtrasService;
use hkyss\Extras\Models\Extras;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract class AbstractExtrasCommand extends Command
{
    protected ExtrasService $extrasService;
    protected LoggerInterface $logger;

    public function __construct(ExtrasService $extrasService, ?LoggerInterface $logger = null)
    {
        parent::__construct();
        $this->extrasService = $extrasService;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @param string $packageName
     * @return bool
     */
    protected function validatePackageName(string $packageName): bool
    {
        return preg_match('/^[a-zA-Z0-9_-]+\/[a-zA-Z0-9_-]+$/', $packageName) === 1;
    }

    /**
     * @param string $version
     * @return bool
     */
    protected function validateVersion(string $version): bool
    {
        if ($version === 'latest') {
            return true;
        }
        
        return preg_match('/^(\d+\.\d+\.\d+|\d+\.\d+|\d+)$/', $version) === 1;
    }

    /**
     * @param string $packageName
     * @param OutputInterface $output
     * @return Extras|null
     */
    protected function getValidatedPackage(string $packageName, OutputInterface $output): ?Extras
    {
        if (!$this->validatePackageName($packageName)) {
            $output->writeln("<error>Invalid package name format: {$packageName}</error>");
            $output->writeln("<comment>Package name should be in format: vendor/package</comment>");
            return null;
        }

        $extra = $this->extrasService->getExtra($packageName);
        if (!$extra) {
            $output->writeln("<error>Package '{$packageName}' not found in extras store</error>");
            return null;
        }

        return $extra;
    }

    /**
     * @param string $operation
     * @param array $context
     */
    protected function logOperation(string $operation, array $context = []): void
    {
        $this->logger->info("extras.{$operation}", $context);
    }

    /**
     * @param OutputInterface $output
     * @param int $maxSteps
     * @return ProgressBar
     */
    protected function createProgressBar(OutputInterface $output, int $maxSteps = 3): ProgressBar
    {
        $progressBar = new ProgressBar($output, $maxSteps);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $progressBar->setBarCharacter('█');
        $progressBar->setEmptyBarCharacter('░');
        $progressBar->setProgressCharacter('█');
        
        return $progressBar;
    }

    /**
     * @param Extras $extra
     * @param OutputInterface $output
     * @param string|null $targetVersion
     */
    protected function displayPackageInfo(Extras $extra, OutputInterface $output, ?string $targetVersion = null): void
    {
        $output->writeln("Package: <info>{$extra->name}</info>");
        $output->writeln("Description: <info>{$extra->description}</info>");
        $output->writeln("Author: <info>{$extra->author}</info>");
        
        if ($targetVersion) {
            $output->writeln("Target Version: <info>{$targetVersion}</info>");
        }
        
        if ($extra->isInstalled()) {
            $installed = $this->extrasService->getInstalledExtras();
            $currentVersion = $installed[$extra->name] ?? 'Unknown';
            $output->writeln("Current Version: <info>{$currentVersion}</info>");
        }
    }

    /**
     * @param \Exception $e
     * @param OutputInterface $output
     * @param string $operation
     * @return int
     */
    protected function handleException(\Exception $e, OutputInterface $output, string $operation): int
    {
        $this->logger->error("extras.{$operation}.error", [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        $output->writeln("<error>Error during {$operation}: " . $e->getMessage() . "</error>");
        
        if ($output->isVerbose()) {
            $output->writeln("<comment>Stack trace:</comment>");
            $output->writeln($e->getTraceAsString());
        }
        
        return Command::FAILURE;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $message
     * @return bool
     */
    protected function confirmOperation(InputInterface $input, OutputInterface $output, string $message): bool
    {
        $output->writeln("\n<comment>{$message} (y/N)</comment>");
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);
        
        return in_array(trim(strtolower($line)), ['y', 'yes']);
    }
}
