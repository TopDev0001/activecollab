<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Search\AdapterFactory;

use ActiveCollab\JobsQueue\JobsDispatcherInterface;
use Angie\Search\Adapter\AdapterInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use ReflectionClass;

/**
 * @package Angie\Search\AdapterFactory
 */
final class SearchAdapterFactory implements SearchAdapterFactoryInterface
{
    private $hosts;
    private $shards;
    private $replicas;
    private $index_name;
    private $document_type;
    private $tenant_id;
    private JobsDispatcherInterface $jobs;
    protected LoggerInterface $logger;

    /**
     * @param int    $shards
     * @param int    $replicas
     * @param string $index_name
     * @param string $document_type
     * @param int    $tenant_id
     */
    public function __construct(
        array $hosts,
        $shards,
        $replicas,
        $index_name,
        $document_type,
        $tenant_id,
        JobsDispatcherInterface $jobs,
        LoggerInterface $logger
    )
    {
        if (!$index_name) {
            throw new InvalidArgumentException('Index name is required.');
        }

        $this->hosts = $hosts;
        $this->shards = $shards;
        $this->replicas = $replicas;
        $this->index_name = $index_name;
        $this->document_type = $document_type;
        $this->tenant_id = $tenant_id;
        $this->jobs = $jobs;
        $this->logger = $logger;
    }

    /**
     * @param  string                $class_name
     * @param  bool                  $is_on_demand
     * @return AdapterInterface|null
     */
    public function produce($class_name, $is_on_demand = false)
    {
        if ($this->isValidAdapterType($class_name)) {
            return new $class_name(
                $this->hosts,
                $this->shards,
                $this->replicas,
                $this->getIndexName(),
                $this->getDocumentType(),
                $this->getTenantId(),
                $this->jobs,
                $this->logger,
                $is_on_demand
            );
        }

        return null;
    }

    private function isValidAdapterType(string $adapter_type): bool
    {
        return class_exists($adapter_type, true) &&
            (new ReflectionClass($adapter_type))->implementsInterface(AdapterInterface::class);
    }

    public function getIndexName()
    {
        return $this->index_name;
    }

    public function getDocumentType()
    {
        return $this->document_type;
    }

    public function getTenantId()
    {
        return $this->tenant_id;
    }
}
