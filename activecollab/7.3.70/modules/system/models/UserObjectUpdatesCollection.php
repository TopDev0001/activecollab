<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class UserObjectUpdatesCollection extends FwUserObjectUpdatesCollection
{
    public function getTimestampHash()
    {
        return sha1(parent::getTimestampHash() . DB::executeFirstCell('SELECT MAX(`updated_on`) FROM `projects`'));
    }

    protected function preloadCounts(array $type_ids_map)
    {
        parent::preloadCounts($type_ids_map);

        if (!empty($type_ids_map[Project::class])) {
            Projects::preloadProjectElementCounts($type_ids_map[Project::class]);
        }
    }
}
