<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateFixDefaultWorkdaysConfiguration extends AngieModelMigration
{
    public function up()
    {
        $workdays = $this->getConfigOptionValue('time_workdays');

        $sanitized = [];

        if (is_array($workdays)) {
            foreach ($workdays as $workday) {
                $workday = (int) $workday;

                if ($workday >= 0 && $workday <= 6) {
                    $sanitized[] = $workday;
                }
            }

            $sanitized = array_unique($sanitized);
            sort($sanitized);
        }

        if (empty($sanitized)) {
            $sanitized = [1, 2, 3, 4, 5];
        }

        $this->setConfigOptionValue('time_workdays', $sanitized);
    }
}
