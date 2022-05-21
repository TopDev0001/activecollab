<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Storage\CapacityCalculatorResolver;

use Angie\Storage\Capacity\StorageCapacityCalculatorInterface;
use Angie\Storage\Capacity\StorageStorageCapacityCalculator;
use Angie\Utils\AccountConfigReader\AccountConfigReaderInterface;
use Angie\Utils\OnDemandStatus\OnDemandStatusInterface;

class StorageCapacityCalculatorResolver implements StorageCapacityCalculatorResolverInterface
{
    private $on_demand_status_resolver;
    private $account_config_reader;

    public function __construct(
        OnDemandStatusInterface $on_demand_status_resolver,
        ?AccountConfigReaderInterface $account_config_reader
    )
    {
        $this->on_demand_status_resolver = $on_demand_status_resolver;
        $this->account_config_reader = $account_config_reader;
    }

    public function getCapacityCalculator(): StorageCapacityCalculatorInterface
    {
        if ($this->on_demand_status_resolver->isOnDemand()) {
            return new StorageStorageCapacityCalculator(
                $this->account_config_reader->getMaxDiskSpace(),
                5,
                php_config_value_to_bytes('100M')
            );
        } else {
            return new StorageStorageCapacityCalculator(
                0,
                0,
                0
            );
        }
    }
}
