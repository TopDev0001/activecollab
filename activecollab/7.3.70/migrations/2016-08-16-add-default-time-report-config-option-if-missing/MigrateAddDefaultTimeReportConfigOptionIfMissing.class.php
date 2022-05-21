<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add default accounting app config option.
 *
 * @package activeCollab.modules.system
 */
class MigrateAddDefaultTimeReportConfigOptionIfMissing extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        if (empty($this->executeFirstCell("SELECT COUNT(*) AS 'row_count' FROM config_options WHERE name = 'time_report_mode'"))) {
            $this->addConfigOption('time_report_mode', 'time_tracking');
        }
    }
}
