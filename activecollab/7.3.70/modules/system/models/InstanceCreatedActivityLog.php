<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Application level instance created activity log implementation.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
class InstanceCreatedActivityLog extends ActivityLog
{
    /**
     * This method is called when we need to load related notification objects for API response.
     */
    public function onRelatedObjectsTypeIdsMap(array &$type_ids_map)
    {
        parent::onRelatedObjectsTypeIdsMap($type_ids_map);

        if ($project_id = $this->getProjectId()) {
            if (empty($type_ids_map[Project::class])) {
                $type_ids_map[Project::class] = [];
            }

            if (!in_array($project_id, $type_ids_map[Project::class])) {
                $type_ids_map[Project::class][] = $project_id;
            }
        }
    }

    /**
     * Return project ID for this subtask.
     *
     * Note: If this comment is not posted on a project element, or project element does not exists, 0 will be returned
     *
     * @return mixed
     */
    public function getProjectId()
    {
        return AngieApplication::cache()->getByObject($this, 'project_id', function () {
            $object = DataObjectPool::get($this->getParentType(), $this->getParentId());

            return $object instanceof IProjectElement ? $object->getProjectId() : 0;
        });
    }
}
