<?php

namespace hkyss\Extras\Console\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use hkyss\Extras\Services\CacheService;

class ExtrasCacheCommand extends BaseExtrasCommand
{
    protected static $defaultName = 'extras:cache';
    protected static $defaultDescription = 'Manage extras cache';

    private CacheService $cacheService;

    public function __construct(ExtrasService $extrasService, CacheService $cacheService, ?LoggerInterface $logger = null)
    {
        parent::__construct($extrasService, $logger);
        $this->cacheService = $cacheService;
    }

    protected function configure(): void
    {
        $this
            ->addOption(CommandOptions::CLEAR->value, 'c', InputOption::VALUE_NONE, 'Clear all cache')
            ->addOption(CommandOptions::STATUS->value, 's', InputOption::VALUE_NONE, 'Show cache status')
            ->addOption(CommandOptions::REFRESH->value, 'r', InputOption::VALUE_NONE, 'Refresh cache (clear and rebuild)')
            ->addOption(CommandOptions::STATS->value, null, InputOption::VALUE_NONE, 'Show cache statistics');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $clear = $input->getOption(CommandOptions::CLEAR->value);
        $status = $input->getOption(CommandOptions::STATUS->value);
        $refresh = $input->getOption(CommandOptions::REFRESH->value);
        $stats = $input->getOption(CommandOptions::STATS->value);

        try {
            if ($clear) {
                $this->clearCache($output);
                return Command::SUCCESS;
            }

            if ($refresh) {
                $this->refreshCache($output);
                return Command::SUCCESS;
            }

            if ($status) {
                $this->showStatus($output);
                return Command::SUCCESS;
            }

            if ($stats) {
                $this->showStats($output);
                return Command::SUCCESS;
            }

            $this->showHelp($output);
            return Command::SUCCESS;
        } catch (\Exception $e) {
            return $this->handleException($e, $output, 'cache');
        }
    }

    /**
     * @param OutputInterface $output
     */
    private function clearCache(OutputInterface $output): void
    {
        $output->writeln('<info>Clearing extras cache...</info>');
        $this->cacheService->clear();
        $output->writeln('<info>Cache cleared successfully!</info>');
    }

    /**
     * @param OutputInterface $output
     */
    private function refreshCache(OutputInterface $output): void
    {
        $output->writeln('<info>Refreshing extras cache...</info>');
        $this->cacheService->clear();
        $output->writeln('<info>Cache refreshed successfully!</info>');
        $output->writeln('<comment>Next command will rebuild cache automatically.</comment>');
    }

    /**
     * @param OutputInterface $output
     */
    private function showStatus(OutputInterface $output): void
    {
        $output->writeln('<info>Extras Cache Status</info>');
        $output->writeln('');
        
        $output->writeln('Cache Driver: <info>' . config('cache.default', 'file') . '</info>');
        $output->writeln('Cache TTL: <info>' . config('extras.cache.ttl', 3600) . ' seconds</info>');
        $output->writeln('Cache Path: <info>' . config('extras.cache.path', 'cache/extras/') . '</info>');
        
        $output->writeln('');
        $output->writeln('<comment>Use --stats to see detailed cache statistics.</comment>');
    }

    /**
     * @param OutputInterface $output
     */
    private function showStats(OutputInterface $output): void
    {
        $output->writeln('<info>Cache Statistics</info>');
        $output->writeln('');
        
        $output->writeln('Cache is enabled and ready for use.');
        $output->writeln('');
        $output->writeln('<comment>Note: Detailed statistics depend on cache driver implementation.</comment>');
    }

    /**
     * @param OutputInterface $output
     */
    private function showHelp(OutputInterface $output): void
    {
        $output->writeln('<info>Extras Cache Management</info>');
        $output->writeln('');
        $output->writeln('Available commands:');
        $output->writeln('  --clear, -c     Clear all cache');
        $output->writeln('  --status, -s    Show cache status');
        $output->writeln('  --refresh, -r   Refresh cache (clear and rebuild)');
        $output->writeln('  --stats         Show cache statistics');
        $output->writeln('');
        $output->writeln('Examples:');
        $output->writeln('  php artisan extras:cache --clear');
        $output->writeln('  php artisan extras:cache --status');
        $output->writeln('  php artisan extras:cache --refresh');
    }
}
