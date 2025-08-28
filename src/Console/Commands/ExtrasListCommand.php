<?php

namespace EvolutionCMS\Extras\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
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
            ->addOption('search', null, InputOption::VALUE_REQUIRED, 'Search extras by name or description')
            ->addOption('list-format', null, InputOption::VALUE_REQUIRED, 'Output format (table, json)', 'table')
            ->addOption('interactive', null, InputOption::VALUE_NONE, 'Enable interactive installation mode');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $installedOnly = $input->getOption('installed');
        $search = $input->getOption('search');
        $format = $input->getOption('list-format');
        $interactive = $input->getOption('interactive');

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

            if ($interactive && !$installedOnly) {
                $this->handleInteractiveMode($input, $output, $extras);
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
        $table->setHeaders(['Name', 'Version', 'Description', 'Author', 'Repository', 'Status']);

        foreach ($extras as $extra) {
            $status = $extra->isInstalled() ? '<info>Installed</info>' : '<comment>Available</comment>';
            
            $table->addRow([
                $extra->getDisplayName(),
                $extra->version,
                $extra->getShortDescription(),
                $extra->author,
                $extra->repository ?: 'Unknown',
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

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param Extras[] $extras
     * @return void
     */
    private function handleInteractiveMode(InputInterface $input, OutputInterface $output, array $extras): void
    {
        if (empty($extras)) {
            $output->writeln('<comment>No extras available for installation.</comment>');
            return;
        }

        $helper = $this->getHelper('question');
        
        $output->writeln('');
        $output->writeln('<info>Interactive Installation Mode</info>');
        $output->writeln('');

        $availableExtras = array_filter($extras, fn($extra) => !$extra->isInstalled());
        
        if (empty($availableExtras)) {
            $output->writeln('<comment>All available extras are already installed.</comment>');
            return;
        }

        $choices = [];
        foreach ($availableExtras as $index => $extra) {
            $choices[$index + 1] = $extra->getDisplayName() . ' (' . $extra->version . ')';
        }
        $choices[0] = 'Exit';

        $question = new ChoiceQuestion(
            'Select an extra to install (or 0 to exit):',
            $choices,
            0
        );
        $question->setErrorMessage('Extra %s is invalid.');

        $selectedIndex = $helper->ask($input, $output, $question);
        
        if ($selectedIndex === 0) {
            $output->writeln('<comment>Installation cancelled.</comment>');
            return;
        }

        $selectedExtra = array_values($availableExtras)[$selectedIndex - 1];
        
        $output->writeln('');
        $output->writeln('<info>Selected: ' . $selectedExtra->getDisplayName() . '</info>');
        $output->writeln('Description: ' . $selectedExtra->description);
        $output->writeln('Author: ' . $selectedExtra->author);
        $output->writeln('Repository: ' . $selectedExtra->repository);
        $output->writeln('');

        $confirmQuestion = new ConfirmationQuestion(
            'Do you want to install this extra? (y/N): ',
            false
        );

        if (!$helper->ask($input, $output, $confirmQuestion)) {
            $output->writeln('<comment>Installation cancelled.</comment>');
            return;
        }

        $output->writeln('');
        $output->writeln('<info>Installing ' . $selectedExtra->getDisplayName() . '...</info>');

        try {
            $this->extrasService->installExtra($selectedExtra->name);
            $output->writeln('<info>Successfully installed ' . $selectedExtra->getDisplayName() . '!</info>');
        } catch (\Exception $e) {
            $output->writeln('<error>Failed to install ' . $selectedExtra->getDisplayName() . ': ' . $e->getMessage() . '</error>');
        }
    }
}
