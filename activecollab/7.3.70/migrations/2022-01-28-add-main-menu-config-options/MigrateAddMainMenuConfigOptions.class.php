<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddMainMenuConfigOptions extends AngieModelMigration
{
    public function up()
    {
        $this->addConfigOption('main_menu_options_order', []);
        $this->addConfigOption('hidden_main_menu_options', []);
    }
}
