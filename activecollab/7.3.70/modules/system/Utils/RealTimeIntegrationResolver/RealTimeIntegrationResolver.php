<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\RealTimeIntegrationResolver;

use ActiveCollab\Module\OnDemand\Integrations\PushIntegration;
use ActiveCollab\Module\OnDemand\Integrations\PushIntegrationInterface;
use ActiveCollab\Module\OnDemand\Utils\PushIntegrationConfigurator\PushIntegrationConfiguratorInterface;
use Integrations;
use PusherIntegration;
use RealTimeIntegrationInterface;

class RealTimeIntegrationResolver implements RealTimeIntegrationResolverInterface
{
    private $is_on_demand;
    private $account_id;
    private $is_available;
    private $push_configurator;

    public function __construct(
        bool $is_on_demand,
        int $account_id,
        bool $is_available = true,
        ?PushIntegrationConfiguratorInterface $push_configurator = null
    )
    {
        $this->is_on_demand = $is_on_demand;
        $this->account_id = $account_id;
        $this->is_available = $is_available;
        $this->push_configurator = $push_configurator;
    }

    public function getIntegration(): ?RealTimeIntegrationInterface
    {
        if ($this->is_on_demand) {
            if ($this->is_available && $this->push_configurator) {
                /** @var RealTimeIntegrationInterface|PushIntegrationInterface $integration */
                $integration = Integrations::findFirstByType(PushIntegration::class);

                $integration->configure($this->account_id, $this->push_configurator);

                return $integration;
            } else {
                return null;
            }
        } else {
            $pusher_integration = Integrations::findFirstByType(PusherIntegration::class);

            if ($pusher_integration instanceof PusherIntegration && $pusher_integration->isInUse()) {
                return $pusher_integration;
            }

            return null;
        }
    }
}
