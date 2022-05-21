<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

trait ITrackingObjectActivityLog
{
    /**
     * This method is called when we need to load related notification objects for API response.
     */
    public function onRelatedObjectsTypeIdsMap(array & $type_ids_map)
    {
        /** @var TimeRecord|Expense|ITrackingObject $tracking_object */
        $tracking_object = $this->getParent();

        if ($tracking_object instanceof ITrackingObject) {
            $tracking_object_parent = $tracking_object->getParent();

            $project_id = null;
            $task_id = null;

            if ($tracking_object_parent instanceof Project) {
                $project_id = $tracking_object_parent->getId();
            } else {
                if ($tracking_object_parent instanceof Task) {
                    $project_id = $tracking_object_parent->getProjectId();
                    $task_id = $tracking_object_parent->getId();
                }
            }

            if ($project_id) {
                if (empty($type_ids_map[Project::class])) {
                    $type_ids_map[Project::class] = [];
                }

                $type_ids_map[Project::class][] = $project_id;
            }

            if ($task_id) {
                if (empty($type_ids_map[Task::class])) {
                    $type_ids_map[Task::class] = [];
                }

                if (!in_array($task_id, $type_ids_map[Task::class])) {
                    $type_ids_map[Task::class][] = $task_id;
                }
            }
        }
    }

    // ---------------------------------------------------
    //  Expectations
    // ---------------------------------------------------

    /**
     * Return parent instance.
     *
     * @return ApplicationObject|null
     */
    abstract public function getParent();
}
