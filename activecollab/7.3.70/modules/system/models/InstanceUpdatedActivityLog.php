<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Inflector;

/**
 * Application level instance updated activity log implementation.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
class InstanceUpdatedActivityLog extends ActivityLog
{
    public function jsonSerialize(): array
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                'modifications' => $this->getModifications(),
            ]
        );
    }

    /**
     * Return modifications.
     *
     * @return array
     */
    public function getModifications()
    {
        $result = $this->getAdditionalProperty('modifications');

        return empty($result) && !is_array($result)
            ? []
            : $result;
    }

    /**
     * Remember modifications.
     *
     * @param  array      $modifications
     * @return array|null
     */
    public function setModifications($modifications)
    {
        if ($modifications && is_foreachable($modifications)) {
            foreach ($modifications as $k => $v) {
                if ($v[0] instanceof DateValue) {
                    $modifications[$k][0] = $v[0]->getTimestamp();
                }

                if ($v[1] instanceof DateValue) {
                    $modifications[$k][1] = $v[1]->getTimestamp();
                }
            }

            return $this->setAdditionalProperty('modifications', $modifications);
        } else {
            return $this->setAdditionalProperty('modifications', []);
        }
    }

    /**
     * Find all modified _id fields and try to load related objects.
     */
    public function onRelatedObjectsTypeIdsMap(array & $type_ids_map)
    {
        foreach ($this->getModifications() as $field => $values) {
            [$old_value, $new_value] = $values;

            if (empty($old_value) && empty($new_value)) {
                continue;
            }

            $type = '';

            if ($field === 'assignee_id') {
                $type = User::class;
            } else {
                if (str_ends_with($field, '_id')) {
                    $type = Inflector::camelize(substr($field, 0, strlen($field) - 3));

                    if (!class_exists($type, true)) {
                        $type = '';
                    }
                }
            }

            if ($type) {
                if (empty($type_ids_map[$type])) {
                    $type_ids_map[$type] = [];
                }

                if ($old_value) {
                    $type_ids_map[$type][] = $old_value;
                }

                if ($new_value) {
                    $type_ids_map[$type][] = $new_value;
                }
            }
        }

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
     * Return project ID for this object.
     *
     * Note: If this comment is not posted on a project element, or project element does not exists, 0 will be returned
     *
     * @return int|null
     */
    public function getProjectId()
    {
        return AngieApplication::cache()->getByObject(
            $this,
            'project_id',
            function () {
                $object = DataObjectPool::get($this->getParentType(), $this->getParentId());

                return $object instanceof IProjectElement ? $object->getProjectId() : 0;
            }
        );
    }
}
