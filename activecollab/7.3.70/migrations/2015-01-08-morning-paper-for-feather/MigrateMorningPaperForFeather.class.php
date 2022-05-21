<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Update morning paper settings for feather.
 *
 * @package
 * @subpackage
 */
class MigrateMorningPaperForFeather extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->removeConfigOption('');
    }
}
