<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\App\Channel;

use ActiveCollab\Foundation\App\Mode\ApplicationModeInterface;
use Angie\Utils\ConstantResolverInterface;
use Angie\Utils\OnDemandStatus\OnDemandStatusInterface;
use AngieApplication;

class OnDemandChannel implements OnDemandChannelInterface
{
    private ApplicationModeInterface $application_mode;
    private OnDemandStatusInterface $on_demand_status;
    private ConstantResolverInterface $constant_resolver;

    public function __construct(
        ApplicationModeInterface $application_mode,
        OnDemandStatusInterface $on_demand_status,
        ConstantResolverInterface $constant_resolver
    )
    {
        $this->application_mode = $application_mode;
        $this->on_demand_status = $on_demand_status;
        $this->constant_resolver = $constant_resolver;
    }

    private ?bool $is_edge = null;

    public function isEdgeChannel(): bool
    {
        if ($this->is_edge === null) {
            $this->is_edge = $this->isChannel(AngieApplication::EDGE_CHANNEL);
        }

        return $this->is_edge;
    }

    private ?bool $is_beta = null;

    public function isBetaChannel(): bool
    {
        if ($this->is_beta === null) {
            $this->is_beta = $this->isChannel(AngieApplication::BETA_CHANNEL);
        }

        return $this->is_beta;
    }

    private ?bool $is_stable = null;

    public function isStableChannel(): bool
    {
        if ($this->is_stable === null) {
            $this->is_stable = !$this->isEdgeChannel() && !$this->isBetaChannel();
        }

        return $this->is_stable;
    }

    private function isChannel(int $channel): bool
    {
        return ($this->application_mode->isInDevelopment() || $this->on_demand_status->isOnDemand())
            && $this->constant_resolver->getValueForConstant('ON_DEMAND_APPLICATION_CHANNEL') === $channel;
    }
}
