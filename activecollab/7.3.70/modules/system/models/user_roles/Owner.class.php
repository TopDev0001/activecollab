<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class Owner extends Member
{
    public function getVisibleCompanyIds(bool $use_cache = true): array
    {
        return AngieApplication::cache()->getByObject(
            $this,
            [
                'visible_companies',
            ],
            function () use ($use_cache) {
                $result = DB::executeFirstColumn('SELECT `id` FROM `companies` ORDER BY `id`');

                if (empty($result)) {
                    $result = [];
                }

                return $result;
            },
            empty($use_cache)
        );
    }
}
