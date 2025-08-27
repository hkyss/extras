<?php

namespace EvolutionCMS\Extras\Exceptions;

class PackageNotFoundException extends ExtrasException
{
    public function __construct(string $packageName)
    {
        parent::__construct("Package '{$packageName}' not found in extras store");
    }
}
