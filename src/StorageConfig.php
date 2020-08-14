<?php

namespace CodingSocks\UploadHandler;

class StorageConfig
{
    /**
     * @var array
     */
    private $config;

    /**
     * StorageConfig constructor.
     * @param $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function getDisk(): string
    {
        return $this->config['disk'];
    }

    public function getChunkDirectory(): string
    {
        return $this->config['directories']['chunk'];
    }

    public function getMergedDirectory(): string
    {
        return $this->config['directories']['merged'];
    }

    public function sweep(): bool
    {
        return $this->config['sweep'];
    }
}
