<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate refactor tax rates table.
 *
 * @package ActiveCollab.migrations
 */
class MigrateRefactorTaxRatesTable extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $tax_rates_table = $this->useTableForAlter('tax_rates');

        // starts altering some fields
        $tax_rates_table->alterColumn('percentage', DBDecimalColumn::create('percentage', 6, 3, 0));
        $tax_rates_table->alterIndex('name', DBIndex::create('name', DBIndex::UNIQUE, ['name', 'percentage']));

        $this->doneUsingTables();
    }
}
