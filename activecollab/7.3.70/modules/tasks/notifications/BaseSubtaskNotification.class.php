<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;

/**
 * Base subtask notification.
 *
 * @package ActiveCollab.modules.tasks
 * @subpackage notifications
 */
abstract class BaseSubtaskNotification extends Notification
{
    /**
     * Serialize to JSON.
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), ['subtask_id' => $this->getSubtaskId()]);
    }

    /**
     * Return subtask ID.
     *
     * @return int
     */
    public function getSubtaskId()
    {
        return $this->getAdditionalProperty('subtask_id');
    }

    /**
     * Set subtask.
     *
     * @return BaseSubtaskNotification
     */
    public function &setSubtask(Subtask $subtask)
    {
        $this->setAdditionalProperty('subtask_id', $subtask->getId());

        return $this;
    }

    public function getAdditionalTemplateVars(NotificationChannel $channel): array
    {
        return [
            'subtask' => $this->getSubtask(),
            'project' => $this->getParent()->getProject(),
        ];
    }

    /**
     * Return subtask instance.
     *
     * @return Subtask|DataObject
     */
    public function getSubtask()
    {
        return DataObjectPool::get(Subtask::class, $this->getSubtaskId());
    }

    /**
     * This method is called when we need to load related notification objects for API response.
     */
    public function onRelatedObjectsTypeIdsMap(array & $type_ids_map)
    {
        if (empty($type_ids_map['Subtask'])) {
            $type_ids_map['Subtask'] = [];
        }

        if (!in_array($this->getSubtaskId(), $type_ids_map['Subtask'])) {
            $type_ids_map['Subtask'][] = $this->getSubtaskId();
        }
    }
}
