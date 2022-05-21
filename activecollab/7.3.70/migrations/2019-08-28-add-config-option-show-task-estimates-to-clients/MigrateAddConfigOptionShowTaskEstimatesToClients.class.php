<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddConfigOptionShowTaskEstimatesToClients extends AngieModelMigration
{
    public function up()
    {
        if (empty($this->executeFirstCell("
                SELECT COUNT(*) AS 'row_count' 
                FROM config_options 
                WHERE name = 'show_task_estimates_to_clients'"))) {
            $this->addConfigOption('show_task_estimates_to_clients', false);
        }
    }
}
