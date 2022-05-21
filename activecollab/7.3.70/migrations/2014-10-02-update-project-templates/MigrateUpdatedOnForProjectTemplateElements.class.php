<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add updated_on column to project template elements model.
 *
 * @package ActiveCollab.modules.system
 * @subpackage migrations
 */
class MigrateUpdatedOnForProjectTemplateElements extends AngieModelMigration
{
    /**
     * Patch template elements.
     */
    public function __construct()
    {
        $this->executeAfter('MigratePatchProjectTemplateElements');
    }

    /**
     * Migrate up.
     */
    public function up()
    {
        $elements = $this->useTableForAlter('project_template_elements');

        $elements->addColumn(new DBUpdatedOnColumn(), 'body');
        $this->execute('UPDATE ' . $elements->getName() . ' SET updated_on = UTC_TIMESTAMP()');

        $this->doneUsingTables();
    }
}
