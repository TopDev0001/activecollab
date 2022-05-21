<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ProjectEvents;

use ActiveCollab\Foundation\Events\WebhookEvent\DataObjectLifeCycleWebhookEventTrait;
use Project;

class ProjectMembershipRevokedEvent extends ProjectLifeCycleEvent implements ProjectMembershipRevokedEventInterface
{
    use DataObjectLifeCycleWebhookEventTrait;

    private array $revoked_user_ids;

    public function __construct(Project $object, array $revoked_user_ids)
    {
        parent::__construct($object);
        $this->revoked_user_ids = $revoked_user_ids;
    }

    public function getWebhookEventType(): string
    {
        return 'ProjectMembershipRevoked';
    }

    public function whoShouldBeNotified(): array
    {
        return array_unique(
            array_merge(
                parent::whoShouldBeNotified(),
                $this->revoked_user_ids,
            )
        );
    }
}
