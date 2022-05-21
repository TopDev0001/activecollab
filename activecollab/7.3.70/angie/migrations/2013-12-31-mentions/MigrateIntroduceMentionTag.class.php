<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateIntroduceMentionTag extends AngieModelMigration
{
    public function up()
    {
        $config_option_name = 'whitelisted_tags';
        $whitelisted_tags = $this->getConfigOptionValue($config_option_name);
        $whitelisted_tags['visual_editor']['span'][] = 'object-id';
        $this->setConfigOptionValue($config_option_name, $whitelisted_tags);
    }
}
