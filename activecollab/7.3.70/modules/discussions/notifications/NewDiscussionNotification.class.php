<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Notifications\PushNotificationInterface;

/**
 * New discussion notification.
 *
 * @package ActiveCollab.modules.discussions
 * @subpackage notifications
 */
class NewDiscussionNotification extends Notification implements PushNotificationInterface
{
    use INewInstanceUpdate; use INewProjectElementNotificationOptOutConfig;

    /**
     * This method is called when we need to load related notification objects for API response.
     */
    public function onRelatedObjectsTypeIdsMap(array &$type_ids_map)
    {
        $discussion = $this->getParent();

        if ($discussion instanceof Discussion && (empty($type_ids_map[Project::class]) || !in_array($discussion->getProjectId(), $type_ids_map[Project::class]))) {
            $type_ids_map[Project::class][] = $discussion->getProjectId();
        }
    }
}
