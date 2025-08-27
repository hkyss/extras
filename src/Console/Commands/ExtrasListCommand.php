<?php

namespace EvolutionCMS\Extras\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use EvolutionCMS\Extras\Services\ExtrasService;
use EvolutionCMS\Extras\Models\Extras;

class ExtrasListCommand extends Command
{
    protected static $defaultName = 'extras:list';
    protected static $defaultDescription = 'List available EvolutionCMS extras';

    private ExtrasService $extrasService;

    public function __construct(ExtrasService $extrasService)
    {
        parent::__construct();
        $this->extrasService = $extrasService;
    }

    protected function configure(): void
    {
        $this
            ->addOption('installed', 'i', InputOption::VALUE_NONE, 'Show only installed extras')
            ->addOption('search', 's', InputOption::VALUE_REQUIRED, 'Search extras by name or description')
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'Output format (table, json)', 'table');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $installedOnly = $input->getOption('installed');
        $search = $input->getOption('search');
        $format = $input->getOption('format');

        try {
            if ($installedOnly) {
                $extras = $this->getInstalledExtras();
            } else {
                $extras = $this->extrasService->getAvailableExtras();
                
                if ($search) {
                    $extras = $this->filterExtras($extras, $search);
                }
            }

            if ($format === 'json') {
                $this->outputJson($output, $extras);
            } else {
                $this->outputTable($output, $extras);
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }

    /**
     * @param OutputInterface $output
     * @param Extras[] $extras
     * @return void
     */
    private function outputTable(OutputInterface $output, array $extras): void
    {
        $table = new Table($output);
        $table->setHeaders(['Name', 'Version', 'Description', 'Author', 'Status']);

        foreach ($extras as $extra) {
            $status = $extra->isInstalled() ? '<info>Installed</info>' : '<comment>Available</comment>';
            
            $table->addRow([
                $extra->getDisplayName(),
                $extra->version,
                $extra->getShortDescription(),
                $extra->author,
                $status
            ]);
        }

        $table->render();
    }

    /**
     * @param OutputInterface $output
     * @param Extras[] $extras
     * @return void
     */
    private function outputJson(OutputInterface $output, array $extras): void
    {
        $data = array_map(fn($extra) => $extra->toArray(), $extras);
        $output->writeln(json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * @return Extras[]
     */
    private function getInstalledExtras(): array
    {
        $installed = $this->extrasService->getInstalledExtras();
        $extras = [];

        foreach ($installed as $packageName => $version) {
            $extra = $this->extrasService->getExtra($packageName);
            if ($extra) {
                $extras[] = $extra;
            }
        }

        return $extras;
    }

    /**
     * @param Extras[] $extras
     * @param string $search
     * @return Extras[]
     */
    private function filterExtras(array $extras, string $search): array
    {
        $search = strtolower($search);
        
        return array_filter($extras, function ($extra) use ($search) {
            return str_contains(strtolower($extra->name), $search) ||
                   str_contains(strtolower($extra->description), $search) ||
                   str_contains(strtolower($extra->author), $search);
        });
    }
}
