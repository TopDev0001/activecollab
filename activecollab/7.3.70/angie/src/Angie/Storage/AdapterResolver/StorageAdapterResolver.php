<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Storage\AdapterResolver;

use ActiveCollab\Foundation\App\AccountId\AccountIdResolverInterface;
use ActiveCollab\JobsQueue\JobsDispatcherInterface;
use ActiveCollab\Logger\LoggerInterface;
use Angie\Storage\ServicesManager\StorageStorageServicesManager;
use Angie\Storage\StorageAdapterInterface;
use IntegrationInterface;
use LocalFilesStorage;
use OnDemandFilesStorage;
use WarehouseIntegration;

class StorageAdapterResolver implements StorageAdapterResolverInterface
{
    private $account_id_resolver;
    private $storage_services_manager;
    private $jobs;
    private $logger;

    public function __construct(
        AccountIdResolverInterface $account_id_resolver,
        StorageStorageServicesManager $storage_services_manager,
        JobsDispatcherInterface $jobs,
        LoggerInterface $logger
    )
    {
        $this->account_id_resolver = $account_id_resolver;
        $this->storage_services_manager = $storage_services_manager;
        $this->jobs = $jobs;
        $this->logger = $logger;
    }

    public function getByIntegration(IntegrationInterface $integration): StorageAdapterInterface
    {
        return $integration instanceof WarehouseIntegration && $integration->isInUse()
            ? new OnDemandFilesStorage(
                $this->account_id_resolver,
                $this->storage_services_manager,
                $this->jobs,
                $integration,
                $this->logger
            )
            : new LocalFilesStorage($this->storage_services_manager, $this->logger);
    }
}
