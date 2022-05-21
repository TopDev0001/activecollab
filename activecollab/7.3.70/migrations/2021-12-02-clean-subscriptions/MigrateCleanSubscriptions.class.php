<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateCleanSubscriptions extends AngieModelMigration
{
    public function up()
    {
        $parent_types = [
            'File',
            'ProjectRequest',
            'RecruitmentCandidate',
            'RecruitmentCandidateConversation',
            'RecruitmentPosition',
            'Repository',
        ];
        $this->executeFirstColumn('DELETE FROM subscriptions WHERE parent_type IN (?)', $parent_types);
    }
}
