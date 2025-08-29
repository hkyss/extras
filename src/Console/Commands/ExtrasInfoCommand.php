<?php

namespace hkyss\Extras\Console\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use hkyss\Extras\Models\Extras;
use hkyss\Extras\Enums\CommandOptions;

class ExtrasInfoCommand extends BaseExtrasCommand
{
    protected static $defaultName = 'extras:info';
    protected static $defaultDescription = 'Show detailed information about an extra';

    protected function configure(): void
    {
        $this
            ->addArgument('package', InputArgument::REQUIRED, 'Package name to get info for')
            ->addOption(CommandOptions::FORMAT->value, null, InputOption::VALUE_REQUIRED, 'Output format (table, json, yaml)', 'table')
            ->addOption(CommandOptions::VERBOSE->value, 'v', InputOption::VALUE_NONE, 'Show verbose information')
            ->addOption(CommandOptions::DEPENDENCIES->value, null, InputOption::VALUE_NONE, 'Show dependency information')
            ->addOption(CommandOptions::RELEASES->value, null, InputOption::VALUE_NONE, 'Show release history');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $package = $input->getArgument('package');
        
        $format = $input->getOption(CommandOptions::FORMAT->value) ?: 'table';
        $verbose = $input->getOption(CommandOptions::VERBOSE->value);
        $showDependencies = $input->getOption(CommandOptions::DEPENDENCIES->value);
        $showReleases = $input->getOption(CommandOptions::RELEASES->value);

        try {
            if (!$this->validatePackageName($package)) {
                $output->writeln("<error>Invalid package name format: {$package}</error>");
                $output->writeln("<comment>Package name should be in format: vendor/package</comment>");
                return Command::FAILURE;
            }

            $extra = $this->getValidatedPackage($package, $output);
            if (!$extra) {
                return Command::FAILURE;
            }

            switch ($format) {
                case 'json':
                    $this->outputJson($output, $extra, $verbose, $showDependencies, $showReleases);
                    break;
                case 'yaml':
                    $this->outputYaml($output, $extra, $verbose, $showDependencies, $showReleases);
                    break;
                default:
                    $this->outputTable($output, $extra, $verbose, $showDependencies, $showReleases);
                    break;
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            return $this->handleException($e, $output, 'info');
        }
    }

    /**
     * @param OutputInterface $output
     * @param Extras $extra
     * @param bool $verbose
     * @param bool $showDependencies
     * @param bool $showReleases
     * @return void
     */
    private function outputTable(OutputInterface $output, Extras $extra, bool $verbose, bool $showDependencies, bool $showReleases): void
    {
        $output->writeln('');
        $output->writeln("<info>📦 Package Information: {$extra->getDisplayName()}</info>");
        $output->writeln('');

        $table = new Table($output);
        $table->setHeaders(['Property', 'Value']);
        $table->setStyle('box');

        $table->addRow(['Name', $extra->name]);
        $table->addRow(['Display Name', $extra->getDisplayName()]);
        $table->addRow(['Version', $extra->version]);
        $table->addRow(['Description', $extra->description]);
        $table->addRow(['Author', $extra->author]);
        $table->addRow(['Repository', $extra->repository ?: 'Unknown']);
        $table->addRow(['Status', $extra->isInstalled() ? '<info>✅ Installed</info>' : '<comment>📦 Available</comment>']);

        if ($extra->isInstalled()) {
            $installed = $this->extrasService->getInstalledExtras();
            $currentVersion = $installed[$extra->name] ?? 'Unknown';
            $table->addRow(['Installed Version', $currentVersion]);
            
            if (version_compare($extra->version, $currentVersion, '>')) {
                $table->addRow(['Update Available', '<info>Yes</info>']);
            } else {
                $table->addRow(['Update Available', '<comment>No</comment>']);
            }
        }

        if ($verbose) {
            $table->addRow(['Homepage', $extra->homepage ?? 'N/A']);
            $table->addRow(['License', $extra->license ?? 'N/A']);
            $table->addRow(['Keywords', $extra->keywords ? implode(', ', $extra->keywords) : 'N/A']);
            $table->addRow(['Support', $extra->support ?? 'N/A']);
            $table->addRow(['Source', $extra->source ?? 'N/A']);
        }

        $table->render();

        if ($showDependencies) {
            $this->showDependencies($output, $extra);
        }

        if ($showReleases) {
            $this->showReleases($output, $extra);
        }

        if ($verbose) {
            $this->showAdditionalInfo($output, $extra);
        }
    }

    /**
     * @param OutputInterface $output
     * @param Extras $extra
     * @param bool $verbose
     * @param bool $showDependencies
     * @param bool $showReleases
     * @return void
     */
    private function outputJson(OutputInterface $output, Extras $extra, bool $verbose, bool $showDependencies, bool $showReleases): void
    {
        $data = $extra->toArray();
        
        if ($verbose) {
            $data['verbose'] = [
                'homepage' => $extra->homepage ?? null,
                'license' => $extra->license ?? null,
                'keywords' => $extra->keywords ?? null,
                'support' => $extra->support ?? null,
                'source' => $extra->source ?? null,
            ];
        }

        if ($showDependencies) {
            $data['dependencies'] = $this->getDependencies($extra);
        }

        if ($showReleases) {
            $data['releases'] = $this->getReleases($extra);
        }

        $output->writeln(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @param OutputInterface $output
     * @param Extras $extra
     * @param bool $verbose
     * @param bool $showDependencies
     * @param bool $showReleases
     * @return void
     */
    private function outputYaml(OutputInterface $output, Extras $extra, bool $verbose, bool $showDependencies, bool $showReleases): void
    {
        $data = $extra->toArray();
        
        if ($verbose) {
            $data['verbose'] = [
                'homepage' => $extra->homepage ?? null,
                'license' => $extra->license ?? null,
                'keywords' => $extra->keywords ?? null,
                'support' => $extra->support ?? null,
                'source' => $extra->source ?? null,
            ];
        }

        if ($showDependencies) {
            $data['dependencies'] = $this->getDependencies($extra);
        }

        if ($showReleases) {
            $data['releases'] = $this->getReleases($extra);
        }

        $output->writeln($this->arrayToYaml($data));
    }

    /**
     * @param OutputInterface $output
     * @param Extras $extra
     * @return void
     */
    private function showDependencies(OutputInterface $output, Extras $extra): void
    {
        $output->writeln('');
        $output->writeln('<info>🔗 Dependencies</info>');
        
        $dependencies = $this->getDependencies($extra);
        
        if (empty($dependencies)) {
            $output->writeln('<comment>No dependencies found.</comment>');
            return;
        }

        $table = new Table($output);
        $table->setHeaders(['Type', 'Package', 'Version Constraint']);
        
        foreach ($dependencies as $type => $deps) {
            foreach ($deps as $package => $constraint) {
                $table->addRow([$type, $package, $constraint]);
            }
        }
        
        $table->render();
    }

    /**
     * @param OutputInterface $output
     * @param Extras $extra
     * @return void
     */
    private function showReleases(OutputInterface $output, Extras $extra): void
    {
        $output->writeln('');
        $output->writeln('<info>📋 Release History</info>');
        
        $releases = $this->getReleases($extra);
        
        if (empty($releases)) {
            $output->writeln('<comment>No release information available.</comment>');
            return;
        }

        $table = new Table($output);
        $table->setHeaders(['Version', 'Release Date', 'Description']);
        
        foreach ($releases as $release) {
            $table->addRow([
                $release['version'],
                $release['date'] ?? 'N/A',
                $release['description'] ?? 'N/A'
            ]);
        }
        
        $table->render();
    }

    /**
     * @param OutputInterface $output
     * @param Extras $extra
     * @return void
     */
    private function showAdditionalInfo(OutputInterface $output, Extras $extra): void
    {
        $output->writeln('');
        $output->writeln('<info>📊 Additional Information</info>');
        
        $table = new Table($output);
        $table->setHeaders(['Property', 'Value']);
        
        if ($extra->homepage) {
            $table->addRow(['Homepage', $extra->homepage]);
        }
        
        if ($extra->license) {
            $table->addRow(['License', $extra->license]);
        }
        
        if ($extra->keywords) {
            $table->addRow(['Keywords', implode(', ', $extra->keywords)]);
        }
        
        if ($extra->support) {
            $table->addRow(['Support', $extra->support]);
        }
        
        if ($extra->source) {
            $table->addRow(['Source', $extra->source]);
        }
        
        if ($table->getRows()) {
            $table->render();
        } else {
            $output->writeln('<comment>No additional information available.</comment>');
        }
    }

    /**
     * @param Extras $extra
     * @return array
     */
    private function getDependencies(Extras $extra): array
    {
        $dependencies = [];
        
        if (isset($extra->require)) {
            $dependencies['require'] = $extra->require;
        }
        
        if (isset($extra->requireDev)) {
            $dependencies['require-dev'] = $extra->requireDev;
        }
        
        if (isset($extra->suggest)) {
            $dependencies['suggest'] = $extra->suggest;
        }
        
        return $dependencies;
    }

    /**
     * @param Extras $extra
     * @return array
     */
    private function getReleases(Extras $extra): array
    {
        $releases = [];
        
        if (isset($extra->releases)) {
            $releases = $extra->releases;
        }
        
        return $releases;
    }

    /**
     * @param array $array
     * @param int $indent
     * @return string
     */
    private function arrayToYaml(array $array, int $indent = 0): string
    {
        $yaml = '';
        $indentStr = str_repeat('  ', $indent);
        
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $yaml .= $indentStr . $key . ":\n";
                $yaml .= $this->arrayToYaml($value, $indent + 1);
            } else {
                $yaml .= $indentStr . $key . ': ' . (is_string($value) ? '"' . addslashes($value) . '"' : $value) . "\n";
            }
        }
        
        return $yaml;
    }
}
