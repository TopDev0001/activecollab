<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Fix project templates table (in case it was broken).
 *
 * @package ActiveCollab.modules.system
 * @subpackage migratioms
 */
class MigratePatchProjectTemplateElements extends AngieModelMigration
{
    /**
     * Make sure that this migration is executed after templates model update.
     */
    public function __construct()
    {
        $this->executeAfter('MigrateProjectTemplatesModelForFeather');
    }

    /**
     * Migrate up.
     */
    public function up()
    {
        $elements = $this->useTableForAlter('project_template_elements');

        if (!$elements->getColumn('position')) {
            $elements->addColumn(DBIntegerColumn::create('position', 10, 0)->setUnsigned(true), 'raw_additional_properties');
        }

        $this->doneUsingTables();
    }
}
