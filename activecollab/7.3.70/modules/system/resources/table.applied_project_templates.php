<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

return DB::createTable('applied_project_templates')->addColumns(
    [
        new DBIdColumn(),
        new DBFkColumn('project_id', 0, true),
        new DBFkColumn('template_id', 0, true),
        new DBCreatedOnByColumn(true, true),
    ],
);
