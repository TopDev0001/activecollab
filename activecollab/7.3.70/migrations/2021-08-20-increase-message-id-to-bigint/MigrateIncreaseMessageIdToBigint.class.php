<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateIncreaseMessageIdToBigint extends AngieModelMigration
{
    public function up()
    {
        $this->execute('ALTER TABLE messages MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT');
    }
}
