<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateInvitedTo extends AngieModelMigration
{
    public function up()
    {
        $invitations = $this->useTableForAlter('user_invitations');

        $invitations->addColumn(
            new DBRelatedObjectColumn('invited_to', false),
            'user_id'
        );
        $invitations->addColumn(new DBUpdatedOnColumn(), 'invited_on');
        $invitations->addColumn(new DBCreatedOnByColumn(), 'invited_on');

        $invitations->dropColumn('invited_on');
        $invitations->dropColumn('accepted_on');

        $this->doneUsingTables();
    }
}
