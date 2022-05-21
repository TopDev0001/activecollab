<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\Webhooks\Resolver;

use ActiveCollab\Foundation\Events\DataObjectLifeCycleEvent\DataObjectLifeCycleEventInterface;

interface RealTimeUsersChannelsResolverInterface
{
    public function getUsersChannels(DataObjectLifeCycleEventInterface $event, bool $for_partial_object = false): array;
}
