<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add created on column to project template element.
 *
 * @package ActiveCollab.migrations
 */
class MigrateCreatedOnForTemplateElements extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $template_elements = $this->useTableForAlter('project_template_elements');

        $template_elements->addColumn(new DBCreatedOnColumn(), 'body');
        $this->execute('UPDATE ' . $template_elements->getName() . ' SET created_on = NOW()');

        $this->doneUsingTables();
    }
}
