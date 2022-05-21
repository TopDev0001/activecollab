<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Notifications\PushNotificationInterface;

/**
 * New note notification.
 *
 * @package ActiveCollab.modules.notes
 * @subpackage notifications
 */
class NewNoteNotification extends Notification implements PushNotificationInterface
{
    use INewInstanceUpdate; use INewProjectElementNotificationOptOutConfig;

    /**
     * This method is called when we need to load related notification objects for API response.
     */
    public function onRelatedObjectsTypeIdsMap(array &$type_ids_map)
    {
        $note = $this->getParent();

        if ($note instanceof Note && (empty($type_ids_map[Project::class]) || !in_array($note->getProjectId(), $type_ids_map[Project::class]))) {
            $type_ids_map[Project::class][] = $note->getProjectId();
        }
    }
}
