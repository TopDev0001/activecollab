<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddArchivedOnToCompanies extends AngieModelMigration
{
    public function up()
    {
        $companies = $this->useTableForAlter('companies');

        $companies->addColumn(
            new DBDateTimeColumn('archived_on'),
            'original_is_archived'
        );

        $this->execute('UPDATE `companies` SET `archived_on` = UTC_TIMESTAMP() WHERE `is_archived` = ?', true);
        $companies->addIndex(new DBIndex('archived_on'));
    }
}
