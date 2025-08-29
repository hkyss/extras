<?php

namespace hkyss\Extras\Traits;

use Symfony\Component\Console\Input\InputInterface;
use hkyss\Extras\Enums\CommandOptions;

trait LegacyOptionsTrait
{
    /**
     * @param InputInterface $input
     * @param CommandOptions $modernOption
     * @param mixed $default
     * @return mixed
     */
    protected function getOptionWithLegacySupport(InputInterface $input, CommandOptions $modernOption, $default = null)
    {
        $value = $input->getOption($modernOption->value);
        
        if ($value !== null && $value !== $default) {
            return $value;
        }
        
        $legacyOption = $modernOption->getLegacyName();
        if ($legacyOption !== $modernOption->value) {
            $legacyValue = $input->getOption($legacyOption);
            if ($legacyValue !== null && $legacyValue !== $default) {
                $this->logOperation('legacy_option_used', [
                    'legacy_option' => $legacyOption,
                    'modern_option' => $modernOption->value,
                    'command' => $this->getName()
                ]);
                
                return $legacyValue;
            }
        }
        
        return $default;
    }

    /**
     * @param InputInterface $input
     * @param CommandOptions $modernOption
     * @return bool
     */
    protected function hasOptionWithLegacySupport(InputInterface $input, CommandOptions $modernOption): bool
    {
        if ($input->hasOption($modernOption->value) && $input->getOption($modernOption->value)) {
            return true;
        }
        
        $legacyOption = $modernOption->getLegacyName();
        if ($legacyOption !== $modernOption->value) {
            if ($input->hasOption($legacyOption) && $input->getOption($legacyOption)) {
                $this->logOperation('legacy_option_used', [
                    'legacy_option' => $legacyOption,
                    'modern_option' => $modernOption->value,
                    'command' => $this->getName()
                ]);
                
                return true;
            }
        }
        
        return false;
    }

    /**
     * @param InputInterface $input
     * @return array
     */
    protected function getLegacyOptionsFromInput(InputInterface $input): array
    {
        $legacyOptions = [];
        
        foreach (CommandOptions::getLegacyOptions() as $legacyOption) {
            if ($input->hasOption($legacyOption->value) && $input->getOption($legacyOption->value)) {
                $legacyOptions[$legacyOption->value] = $input->getOption($legacyOption->value);
            }
        }
        
        return $legacyOptions;
    }

    /**
     * @param InputInterface $input
     * @return bool
     */
    protected function validateNoConflictingOptions(InputInterface $input): bool
    {
        $conflicts = [
            [
                'modern' => CommandOptions::VERSION,
                'legacy' => [CommandOptions::INSTALL_VERSION, CommandOptions::UPDATE_VERSION]
            ],
            [
                'modern' => CommandOptions::FORCE,
                'legacy' => [CommandOptions::INSTALL_FORCE, CommandOptions::UPDATE_FORCE, CommandOptions::REMOVE_FORCE]
            ],
            [
                'modern' => CommandOptions::DRY_RUN,
                'legacy' => [CommandOptions::BATCH_INSTALL_DRY_RUN, CommandOptions::BATCH_UPDATE_DRY_RUN, CommandOptions::BATCH_REMOVE_DRY_RUN]
            ]
        ];
        
        foreach ($conflicts as $conflict) {
            $modernSet = $input->hasOption($conflict['modern']->value) && $input->getOption($conflict['modern']->value);
            
            foreach ($conflict['legacy'] as $legacyOption) {
                $legacySet = $input->hasOption($legacyOption->value) && $input->getOption($legacyOption->value);
                
                if ($modernSet && $legacySet) {
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * @param InputInterface $input
     * @return void
     */
    protected function showLegacyOptionWarnings(InputInterface $input): void
    {
        $legacyOptions = $this->getLegacyOptionsFromInput($input);
        
        if (!empty($legacyOptions)) {
            $this->logOperation('legacy_options_detected', [
                'options' => array_keys($legacyOptions),
                'command' => $this->getName()
            ]);
        }
    }
}
