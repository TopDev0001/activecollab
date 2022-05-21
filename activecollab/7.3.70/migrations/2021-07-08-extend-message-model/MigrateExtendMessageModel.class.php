<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Module\System\Model\Message\UserMessage;

class MigrateExtendMessageModel extends AngieModelMigration
{
    public function up()
    {
        $messages = $this->useTableForAlter('messages');

        if (!$messages->getColumn('type')) {
            $messages->addColumn(
                new DBTypeColumn(),
                'id'
            );
        }
        if (!$messages->getColumn('raw_additional_properties')) {
            $messages->addColumn(
                new DBAdditionalPropertiesColumn(),
                'updated_on'
            );
        }

        $this->doneUsingTables();

        $this->execute(
            'UPDATE messages SET type = ?',
            UserMessage::class
        );
    }
}
