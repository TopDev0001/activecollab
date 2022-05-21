<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateSetOwnerCompany extends AngieModelMigration
{
    public function up()
    {
        $owner_company_id = (int) DB::executeFirstCell('SELECT id FROM companies WHERE is_owner = ? LIMIT 0, 1', [true]);

        if ($owner_company_id) {
            $project_ids = DB::executeFirstColumn('
                SELECT p.id
                FROM projects AS p
                LEFT JOIN companies AS c ON p.company_id = c.id
                WHERE c.is_archived = ? OR c.is_trashed = ? OR c.id IS NULL;
            ', true, true);

            if ($project_ids) {
                DB::execute('UPDATE projects SET company_id = ? WHERE id IN (?)', $owner_company_id, $project_ids);
            }
        }
    }
}
