<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddShowRecentlyCompletedProjectsInInvoiceFormConfigOption extends AngieModelMigration
{
    public function up()
    {
        if (!ConfigOptions::exists('show_recently_completed_projects_in_invoice_form')) {
            $this->addConfigOption('show_recently_completed_projects_in_invoice_form', true);
        }
    }
}
