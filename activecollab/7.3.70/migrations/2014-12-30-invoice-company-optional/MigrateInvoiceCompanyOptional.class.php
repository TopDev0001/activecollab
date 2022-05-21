<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Make company_id optional for invoices.
 *
 * @package ActiveCollab.migrations
 */
class MigrateInvoiceCompanyOptional extends AngieModelMigration
{
    /**
     * @var array
     */
    private $id_name_map = false;

    /**
     * Migrate up.
     */
    public function up()
    {
        $invoices = $this->useTableForAlter('invoices');
        $estimates = $this->useTableForAlter('estimates');
        $recurring_profiles = $this->useTableForAlter('recurring_profiles');

        /** @var DBTable $table */
        foreach ([$invoices, $estimates, $recurring_profiles] as $table) {
            $this->execute('UPDATE ' . $table->getName() . ' SET company_id = ? WHERE company_id IS NULL', 0);

            if ($rows = $this->execute('SELECT id, company_id FROM ' . $table->getName() . ' WHERE company_name IS NULL OR company_name = ?', '')) {
                foreach ($rows as $row) {
                    $this->execute('UPDATE ' . $table->getName() . ' SET company_name = ? WHERE id = ?', $this->getCompanyNameById($row['company_id']), $row['id']);
                }
            }

            $table->alterColumn('company_id', DBFkColumn::create('company_id'));
        }

        $this->doneUsingTables();
    }

    /**
     * Return company name by company ID.
     *
     * @param  int    $company_id
     * @return string
     */
    private function getCompanyNameById($company_id)
    {
        if ($this->id_name_map === false) {
            $this->id_name_map = [];

            foreach ($this->execute('SELECT id, name FROM ' . $this->useTables('companies')[0]) as $row) {
                $this->id_name_map[$row['id']] = $row['name'];
            }
        }

        return isset($this->id_name_map[$company_id]) && $this->id_name_map[$company_id] ? $this->id_name_map[$company_id] : 'Unknown INC';
    }
}
