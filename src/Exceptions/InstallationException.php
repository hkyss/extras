<?php

namespace EvolutionCMS\Extras\Exceptions;

class InstallationException extends ExtrasStoreException
{
    public function __construct(string $packageName, string $reason = '')
    {
        $message = "Failed to install package '{$packageName}'";
        if ($reason) {
            $message .= ": {$reason}";
        }
        parent::__construct($message);
    }
}
