<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateEnableDecimalsForIranianRial extends AngieModelMigration
{
    public function up()
    {
        $this->execute('UPDATE `currencies` SET `decimal_spaces` = "2" WHERE `code` = "IRR";');
    }
}
