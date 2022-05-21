<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\FeatureFlags;

use ActiveCollab\Foundation\App\AccountId\AccountIdResolverInterface;
use ActiveCollab\Foundation\App\Channel\OnDemandChannelInterface;

class FeatureFlags implements FeatureFlagsInterface
{
    private AccountIdResolverInterface $account_id_resolver;
    private FeatureFlagsStringResolverInterface $feature_flags_string_resolver;
    private OnDemandChannelInterface $on_demand_channel;

    public function __construct(
        AccountIdResolverInterface $account_id_resolver,
        OnDemandChannelInterface $on_demand_channel,
        FeatureFlagsStringResolverInterface $feature_flags_string_resolver
    )
    {
        $this->account_id_resolver = $account_id_resolver;
        $this->on_demand_channel = $on_demand_channel;
        $this->feature_flags_string_resolver = $feature_flags_string_resolver;
    }

    private ?array $feature_flags = null;

    public function getFeatureFlags(): array
    {
        if ($this->feature_flags === null) {
            $this->feature_flags = $this->parseFeatureFlagsString(
                trim(
                    $this->feature_flags_string_resolver->getString()
                )
            );
        }

        return $this->feature_flags;
    }

    private ?string $channel_modifier = null;

    private function getChannelModifier(): string
    {
        if ($this->channel_modifier === null) {
            if ($this->on_demand_channel->isEdgeChannel()) {
                $this->channel_modifier = FeatureFlagsInterface::EDGE_CHANNEL_MODIFIER;
            } elseif ($this->on_demand_channel->isBetaChannel()) {
                $this->channel_modifier = FeatureFlagsInterface::BETA_CHANNEL_MODIFIER;
            } else {
                $this->channel_modifier = FeatureFlagsInterface::STABLE_CHANNEL_MODIFIER;
            }
        }

        return $this->channel_modifier;
    }

    public function isEnabled(string $feature_flag): bool
    {
        return in_array($feature_flag, $this->getFeatureFlags());
    }

    public function jsonSerialize(): array
    {
        return $this->getFeatureFlags();
    }

    private function parseFeatureFlagsString(string $feature_flags_string): array
    {
        $result = [];

        if ($feature_flags_string) {
            $feature_flags = explode(',', $feature_flags_string);

            foreach ($feature_flags as $feature_flag) {
                $modifier = '';
                $modifier_pos = strpos($feature_flag, '/');

                if ($modifier_pos !== false) {
                    $modifier = substr($feature_flag, $modifier_pos + 1);
                    $feature_flag = substr($feature_flag, 0, $modifier_pos);
                }

                if ($this->shouldInclude($modifier)) {
                    $result[] = $feature_flag;
                }
            }
        }

        return $result;
    }

    private function shouldInclude(string $modifier): bool
    {
        if (empty($modifier)) {
            return true;
        }

        if (ctype_digit($modifier)) {
            return $this->shouldIncludeByAcccountId((int) $modifier);
        } else {
            return $this->shouldIncludeByChannel($modifier);
        }
    }

    private function shouldIncludeByChannel(string $modifier): bool
    {
        switch ($modifier) {
            case FeatureFlagsInterface::EDGE_CHANNEL_MODIFIER:
                return $modifier === $this->getChannelModifier();

            case FeatureFlagsInterface::BETA_CHANNEL_MODIFIER:
                return $modifier === $this->getChannelModifier()
                    || $this->getChannelModifier() === FeatureFlagsInterface::EDGE_CHANNEL_MODIFIER;

            case FeatureFlagsInterface::STABLE_CHANNEL_MODIFIER:
                return true;

            default:
                return false;
        }
    }

    private function shouldIncludeByAcccountId(int $modifier): bool
    {
        if ($modifier === 1) {
            return $this->account_id_resolver->getAccountId() % 2 > 0;
        }

        return empty($this->account_id_resolver->getAccountId() % $modifier);
    }
}
