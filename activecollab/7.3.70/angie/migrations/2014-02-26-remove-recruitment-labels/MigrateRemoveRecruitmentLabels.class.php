<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Remove recruitment labels.
 *
 * @package angie.migrations
 */
class MigrateRemoveRecruitmentLabels extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->execute('DELETE FROM labels WHERE type = ?', 'RecruitmentCandidatePositionLabel');
    }
}
