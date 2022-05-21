<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateNewFeaturesConfigOptions extends AngieModelMigration
{
    public function up()
    {
        $this->addConfigOption('new_features_notification', true);
        $this->addConfigOption('new_features_timestamp', null);
    }
}
