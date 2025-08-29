<?php

namespace hkyss\Extras\Console\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class HelpCommand extends AbstractExtrasCommand
{
    protected static $defaultName = 'extras:help';
    protected static $defaultDescription = 'Show help for EvolutionCMS extras commands';

    /**
     * @param void
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->addArgument('command', InputArgument::OPTIONAL, 'Command name to show help for')
            ->setHelp('This command displays help information for EvolutionCMS extras commands.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $commandName = $input->getArgument('command');

        if ($commandName) {
            return $this->showCommandHelp($output, $commandName);
        } else {
            return $this->showGeneralHelp($output);
        }
    }

    /**
     * @param OutputInterface $output
     * @return int
     */
    private function showGeneralHelp(OutputInterface $output): int
    {
        $output->writeln("<info>EvolutionCMS Extras - Package Management Tool</info>");
        $output->writeln("");

        $table = new Table($output);
        $table->setHeaders(['Command', 'Description']);

        $commands = [
            'extras:list' => 'List all available extras',
            'extras:info <package>' => 'Show detailed information about a package',
            'extras:install <package>' => 'Install an extra package',
            'extras:update [package]' => 'Update installed packages',
            'extras:remove <package>' => 'Remove an installed package',
            'extras:cache' => 'Manage cache operations',
            'extras:batch:install <packages...>' => 'Install multiple packages at once',
            'extras:batch:update <packages...>' => 'Update multiple packages at once',
            'extras:batch:remove <packages...>' => 'Remove multiple packages at once',
            'extras:help [command]' => 'Show this help or help for specific command'
        ];

        foreach ($commands as $command => $description) {
            $table->addRow([$command, $description]);
        }

        $table->render();

        $output->writeln("");
        $output->writeln("<comment>Examples:</comment>");
        $output->writeln("  php artisan extras:list");
        $output->writeln("  php artisan extras:install vendor/package");
        $output->writeln("  php artisan extras:update vendor/package --version=1.2.3");
        $output->writeln("  php artisan extras:remove vendor/package --force");
        $output->writeln("  php artisan extras:batch:install vendor/package1 vendor/package2");
        $output->writeln("  php artisan extras:help install");

        return Command::SUCCESS;
    }

    /**
     * @param OutputInterface $output
     * @param string $commandName
     * @return int
     */
    private function showCommandHelp(OutputInterface $output, string $commandName): int
    {
        $helpData = $this->getCommandHelpData();

        if (!isset($helpData[$commandName])) {
            $output->writeln("<error>Unknown command: {$commandName}</error>");
            $output->writeln("Use 'extras:help' to see all available commands.");
            return Command::FAILURE;
        }

        $data = $helpData[$commandName];
        
        $output->writeln("<info>Command: {$commandName}</info>");
        $output->writeln("<info>Description: {$data['description']}</info>");
        $output->writeln("");

        if (!empty($data['usage'])) {
            $output->writeln("<comment>Usage:</comment>");
            foreach ($data['usage'] as $usage) {
                $output->writeln("  {$usage}");
            }
            $output->writeln("");
        }

        if (!empty($data['arguments'])) {
            $output->writeln("<comment>Arguments:</comment>");
            $table = new Table($output);
            $table->setHeaders(['Name', 'Description', 'Required']);
            
            foreach ($data['arguments'] as $arg) {
                $table->addRow([$arg['name'], $arg['description'], $arg['required'] ? 'Yes' : 'No']);
            }
            $table->render();
            $output->writeln("");
        }

        if (!empty($data['options'])) {
            $output->writeln("<comment>Options:</comment>");
            $table = new Table($output);
            $table->setHeaders(['Option', 'Description']);
            
            foreach ($data['options'] as $option) {
                $table->addRow([$option['name'], $option['description']]);
            }
            $table->render();
            $output->writeln("");
        }

        if (!empty($data['examples'])) {
            $output->writeln("<comment>Examples:</comment>");
            foreach ($data['examples'] as $example) {
                $output->writeln("  {$example}");
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @return array
     */
    private function getCommandHelpData(): array
    {
        return [
            'extras:list' => [
                'description' => 'List all available extras from the repository',
                'usage' => [
                    'php artisan extras:list',
                    'php artisan extras:list --installed',
                    'php artisan extras:list --available'
                ],
                'arguments' => [],
                'options' => [
                    ['name' => '--installed', 'description' => 'Show only installed packages'],
                    ['name' => '--available', 'description' => 'Show only available packages'],
                    ['name' => '--format=table|json', 'description' => 'Output format']
                ],
                'examples' => [
                    'php artisan extras:list',
                    'php artisan extras:list --installed',
                    'php artisan extras:list --format=json'
                ]
            ],
            'extras:info' => [
                'description' => 'Show detailed information about a specific package',
                'usage' => [
                    'php artisan extras:info <package>'
                ],
                'arguments' => [
                    ['name' => 'package', 'description' => 'Package name (vendor/package)', 'required' => true]
                ],
                'options' => [
                    ['name' => '--verbose', 'description' => 'Show additional information']
                ],
                'examples' => [
                    'php artisan extras:info vendor/package',
                    'php artisan extras:info vendor/package --verbose'
                ]
            ],
            'extras:install' => [
                'description' => 'Install an extra package',
                'usage' => [
                    'php artisan extras:install <package> [--version=<version>] [--force] [--no-deps]'
                ],
                'arguments' => [
                    ['name' => 'package', 'description' => 'Package name to install (vendor/package)', 'required' => true]
                ],
                'options' => [
                    ['name' => '--version', 'description' => 'Version to install (default: latest)'],
                    ['name' => '--force', 'description' => 'Force installation even if already installed'],
                    ['name' => '--no-deps', 'description' => 'Skip dependency installation']
                ],
                'examples' => [
                    'php artisan extras:install vendor/package',
                    'php artisan extras:install vendor/package --version=1.2.3',
                    'php artisan extras:install vendor/package --force'
                ]
            ],
            'extras:update' => [
                'description' => 'Update installed packages',
                'usage' => [
                    'php artisan extras:update [package] [--version=<version>] [--force] [--check-only]'
                ],
                'arguments' => [
                    ['name' => 'package', 'description' => 'Package name to update (optional, updates all if not specified)', 'required' => false]
                ],
                'options' => [
                    ['name' => '--version', 'description' => 'Version to update to (default: latest)'],
                    ['name' => '--force', 'description' => 'Force update even if already at latest version'],
                    ['name' => '--check-only', 'description' => 'Only check for updates without installing']
                ],
                'examples' => [
                    'php artisan extras:update',
                    'php artisan extras:update vendor/package',
                    'php artisan extras:update vendor/package --version=2.0.0',
                    'php artisan extras:update --check-only'
                ]
            ],
            'extras:remove' => [
                'description' => 'Remove an installed package',
                'usage' => [
                    'php artisan extras:remove <package> [--force] [--keep-deps]'
                ],
                'arguments' => [
                    ['name' => 'package', 'description' => 'Package name to remove (vendor/package)', 'required' => true]
                ],
                'options' => [
                    ['name' => '--force', 'description' => 'Force removal without confirmation'],
                    ['name' => '--keep-deps', 'description' => 'Keep dependencies if not used by other packages']
                ],
                'examples' => [
                    'php artisan extras:remove vendor/package',
                    'php artisan extras:remove vendor/package --force',
                    'php artisan extras:remove vendor/package --keep-deps'
                ]
            ],
            'extras:cache' => [
                'description' => 'Manage cache operations',
                'usage' => [
                    'php artisan extras:cache [clear|refresh|status]'
                ],
                'arguments' => [
                    ['name' => 'operation', 'description' => 'Cache operation (clear, refresh, status)', 'required' => false]
                ],
                'options' => [
                    ['name' => '--force', 'description' => 'Force cache operations without confirmation']
                ],
                'examples' => [
                    'php artisan extras:cache',
                    'php artisan extras:cache clear',
                    'php artisan extras:cache refresh --force'
                ]
            ],
            'extras:batch:install' => [
                'description' => 'Install multiple packages at once',
                'usage' => [
                    'php artisan extras:batch:install <packages...> [--file=<file>] [--force] [--dry-run]'
                ],
                'arguments' => [
                    ['name' => 'packages', 'description' => 'List of package names to install', 'required' => false]
                ],
                'options' => [
                    ['name' => '--file', 'description' => 'File containing package list (one per line)'],
                    ['name' => '--force', 'description' => 'Skip confirmation prompts'],
                    ['name' => '--dry-run', 'description' => 'Show what would be done without actually doing it'],
                    ['name' => '--parallel', 'description' => 'Number of parallel operations (default: 1)']
                ],
                'examples' => [
                    'php artisan extras:batch:install vendor/package1 vendor/package2',
                    'php artisan extras:batch:install --file=packages.txt',
                    'php artisan extras:batch:install --file=packages.txt --dry-run'
                ]
            ],
            'extras:batch:update' => [
                'description' => 'Update multiple packages at once',
                'usage' => [
                    'php artisan extras:batch:update <packages...> [--file=<file>] [--force] [--dry-run]'
                ],
                'arguments' => [
                    ['name' => 'packages', 'description' => 'List of package names to update', 'required' => false]
                ],
                'options' => [
                    ['name' => '--file', 'description' => 'File containing package list (one per line)'],
                    ['name' => '--force', 'description' => 'Skip confirmation prompts'],
                    ['name' => '--dry-run', 'description' => 'Show what would be done without actually doing it'],
                    ['name' => '--parallel', 'description' => 'Number of parallel operations (default: 1)']
                ],
                'examples' => [
                    'php artisan extras:batch:update vendor/package1 vendor/package2',
                    'php artisan extras:batch:update --file=packages.txt',
                    'php artisan extras:batch:update --file=packages.txt --dry-run'
                ]
            ],
            'extras:batch:remove' => [
                'description' => 'Remove multiple packages at once',
                'usage' => [
                    'php artisan extras:batch:remove <packages...> [--file=<file>] [--force] [--dry-run]'
                ],
                'arguments' => [
                    ['name' => 'packages', 'description' => 'List of package names to remove', 'required' => false]
                ],
                'options' => [
                    ['name' => '--file', 'description' => 'File containing package list (one per line)'],
                    ['name' => '--force', 'description' => 'Skip confirmation prompts'],
                    ['name' => '--dry-run', 'description' => 'Show what would be done without actually doing it'],
                    ['name' => '--parallel', 'description' => 'Number of parallel operations (default: 1)']
                ],
                'examples' => [
                    'php artisan extras:batch:remove vendor/package1 vendor/package2',
                    'php artisan extras:batch:remove --file=packages.txt',
                    'php artisan extras:batch:remove --file=packages.txt --dry-run'
                ]
            ]
        ];
    }
}
