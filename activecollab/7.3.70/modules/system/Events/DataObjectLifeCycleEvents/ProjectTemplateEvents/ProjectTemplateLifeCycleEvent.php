<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ProjectTemplateEvents;

use ActiveCollab\Foundation\Events\DataObjectLifeCycleEvent\DataObjectLifeCycleEvent;
use Member;
use ProjectTemplate;
use User;
use Users;

abstract class ProjectTemplateLifeCycleEvent extends DataObjectLifeCycleEvent implements ProjectTemplateLifeCycleEventInterface
{
    public function __construct(ProjectTemplate $object)
    {
        parent::__construct($object);
    }

    public function whoShouldBeNotified(): array
    {
        $owner_ids = Users::findOwnerIds();
        $members_can_manage_projects = Users::findIdsByType(
            Member::class,
            null,
            function ($id, $type, $custom_permissions) {
                return in_array(User::CAN_MANAGE_PROJECTS, $custom_permissions);
            }
        );

        return array_unique(
            array_merge(
                $owner_ids ?: [],
                $members_can_manage_projects ?: []
            )
        );
    }
}
