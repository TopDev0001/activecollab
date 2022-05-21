<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\Webhooks\Resolver;

use ActiveCollab\Foundation\Events\DataObjectLifeCycleEvent\DataObjectLifeCycleEventInterface;
use Angie\Authentication;
use Angie\FeatureFlags\FeatureFlagsInterface;
use AngieApplication;
use Member;
use User;
use Users;

class RealTimeUsersChannelsResolver implements RealTimeUsersChannelsResolverInterface
{
    private Authentication $authentication;
    private FeatureFlagsInterface $feature_flags;

    public function __construct(
        Authentication $authentication,
        FeatureFlagsInterface $feature_flags
    )
    {
        $this->authentication = $authentication;
        $this->feature_flags = $feature_flags;
    }

    public function getUsersChannels(DataObjectLifeCycleEventInterface $event, bool $for_partial_object = false): array
    {
        $user_ids = $event->whoShouldBeNotified();

        if ($for_partial_object) {
            // send partial data to all member plus users who cannot see the object
            $user_ids = Users::findIdsByType(
                Member::class,
                $user_ids,
                function ($id, $type, $custom_permissions) {
                    return in_array(User::CAN_MANAGE_PROJECTS, $custom_permissions);
                }
            );

            $user_ids = is_array($user_ids) ? $user_ids : [];
        }

        $this->excludeLoggedUserId($user_ids);

        return $this->makeChannels($user_ids);
    }

    private function excludeLoggedUserId(array &$user_ids): void
    {
        if (
            ($key = array_search($this->authentication->getLoggedUserId(), $user_ids)) !== false &&
            $this->feature_flags->isEnabled('disable_own_real_time_events')
        ) {
            unset($user_ids[$key]);
        }
    }

    private function makeChannels(array $user_ids): array
    {
        $channels = [];

        foreach ($user_ids as $user_id) {
            if (AngieApplication::isOnDemand()) {
                $channels[] = sprintf(
                    'private-instance-%s-user-%s',
                    AngieApplication::getAccountId(),
                    $user_id
                );
            } else {
                $channels[] = sprintf(
                    'private-user-%s',
                    $user_id
                );
            }
        }

        return $channels;
    }
}
