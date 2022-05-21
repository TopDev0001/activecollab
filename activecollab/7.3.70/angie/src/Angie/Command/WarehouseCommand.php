<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Command;

use Integrations;
use RuntimeException;
use WarehouseIntegration;
use WarehouseIntegrationInterface;

abstract class WarehouseCommand extends Command
{
    protected function getCommandNamePrefix(): string
    {
        return 'warehouse:';
    }

    protected function getWarehouseIntegration(): WarehouseIntegrationInterface
    {
        $integration = Integrations::findFirstByType(WarehouseIntegration::class);

        if (!$integration instanceof WarehouseIntegrationInterface) {
            throw new RuntimeException('Failed to load Warehouse integration.');
        }

        return $integration;
    }

    protected function isWarehouseIntegrationConfigured(): bool
    {
        return (bool) $this->getWarehouseIntegration()->isInUse();
    }

    protected function getClientId(): ?string
    {
        return $this->getWarehouseIntegration()->getClientId();
    }

    protected function getClientSecret(): ?string
    {
        return $this->getWarehouseIntegration()->getSecret();
    }

    protected function getStoreId(): ?int
    {
        return $this->getWarehouseIntegration()->getStoreId();
    }

    protected function getAccessToken(): ?string
    {
        return $this->getWarehouseIntegration()->getAccessToken();
    }
}
