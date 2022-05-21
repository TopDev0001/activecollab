<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class ActivityLog extends BaseActivityLog
{
    public function jsonSerialize(): array
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                'created_by_name' => $this->getCreatedByName(),
                'created_by_email' => $this->getCreatedByEmail(),
                'parent_path' => $this->getParentPath(),
            ]
        );
    }

    public function getRoutingContext(): string
    {
        return 'activity_log';
    }

    public function getRoutingContextParams(): array
    {
        return [
            'activity_log_id' => $this->getId(),
        ];
    }

    /**
     * This method is called when we need to load related notification objects for API response.
     */
    public function onRelatedObjectsTypeIdsMap(array & $type_ids_map)
    {
    }
}
