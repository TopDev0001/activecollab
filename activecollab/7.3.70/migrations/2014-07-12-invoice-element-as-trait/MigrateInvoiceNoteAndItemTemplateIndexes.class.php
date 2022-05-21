<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add invoice note and template indexes.
 *
 * @package activeCollab.modules.invoicing
 * @subpackage migrations
 */
class MigrateInvoiceNoteAndItemTemplateIndexes extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        foreach (['invoice_note_templates', 'invoice_item_templates'] as $table_name) {
            $table = $this->useTableForAlter($table_name);

            if ($table->getColumn('position') instanceof DBColumn) {
                $this->execute('UPDATE ' . $table->getName() . ' SET position = ? WHERE position IS NULL OR position < ?', 0, 1);
                $table->alterColumn('position', DBIntegerColumn::create('position', 10, 0)->setUnsigned(true));
            } else {
                $table->addColumn(DBIntegerColumn::create('position', 10, 0)->setUnsigned(true));
            }

            if (!$table->indexExists('position')) {
                $table->addIndex(DBIndex::create('position'));
            }
        }

        $this->doneUsingTables();
    }
}
