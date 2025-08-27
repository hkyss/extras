<?php

namespace EvolutionCMS\Extras\Models;

class Extras
{
    public string $name;
    public string $description;
    public string $version;
    public string $author;
    public string $license;
    public array $keywords;
    public string $homepage;
    public array $require;
    public array $suggest;
    public string $type;
    public string $distUrl;
    public string $sourceUrl;
    public array $support;
    public array $autoload;
    public array $extra;
    public string $createdAt;
    public string $updatedAt;
    public string $repository = '';

    public function __construct(array $data)
    {
        $this->name = $data['name'] ?? '';
        $this->description = $data['description'] ?? '';
        $this->version = $data['version'] ?? '';
        $this->author = $data['author'] ?? '';
        $this->license = $data['license'] ?? '';
        $this->keywords = $data['keywords'] ?? [];
        $this->homepage = $data['homepage'] ?? '';
        $this->require = $data['require'] ?? [];
        $this->suggest = $data['suggest'] ?? [];
        $this->type = $data['type'] ?? '';
        $this->distUrl = $data['dist']['url'] ?? '';
        $this->sourceUrl = $data['source']['url'] ?? '';
        $this->support = $data['support'] ?? [];
        $this->autoload = $data['autoload'] ?? [];
        $this->extra = $data['extra'] ?? [];
        $this->createdAt = $data['created_at'] ?? '';
        $this->updatedAt = $data['updated_at'] ?? '';
    }

    /**
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getShortDescription(): string
    {
        return mb_strlen($this->description) > 100 
            ? mb_substr($this->description, 0, 97) . '...' 
            : $this->description;
    }

    /**
     * @return bool
     */
    public function isInstalled(): bool
    {
        $packageManager = new \EvolutionCMS\Extras\Managers\ComposerPackageManager();
        return $packageManager->isInstalled($this->name);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'version' => $this->version,
            'author' => $this->author,
            'license' => $this->license,
            'keywords' => $this->keywords,
            'homepage' => $this->homepage,
            'require' => $this->require,
            'suggest' => $this->suggest,
            'type' => $this->type,
            'dist_url' => $this->distUrl,
            'source_url' => $this->sourceUrl,
            'support' => $this->support,
            'autoload' => $this->autoload,
            'extra' => $this->extra,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
