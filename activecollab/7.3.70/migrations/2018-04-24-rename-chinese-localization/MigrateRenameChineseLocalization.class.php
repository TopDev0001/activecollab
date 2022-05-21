<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateRenameChineseLocalization extends AngieModelMigration
{
    public function up()
    {
        $this->execute('UPDATE `languages` SET `name` = ? WHERE `locale` = ?', '简体中文', 'zh_CN.UTF-8');
    }
}
