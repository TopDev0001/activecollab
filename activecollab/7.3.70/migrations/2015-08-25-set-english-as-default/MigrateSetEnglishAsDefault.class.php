<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Change system language to english. Set all users language to english.
 *
 * @package activeCollab.modules.system
 */
class MigrateSetEnglishAsDefault extends AngieModelMigration
{
    public function up()
    {
        $english = $this->executeFirstRow('SELECT id, is_default FROM languages WHERE locale = ?', 'en_US.UTF-8');

        if (!empty($english)) {
            if ((int) $english['is_default'] == 0) {
                $this->execute('UPDATE languages SET is_default = ? WHERE id != ?', 0, $english['id']);
                $this->execute('UPDATE languages SET is_default = ? WHERE id = ?', 1, $english['id']);
            }

            $this->execute('UPDATE users SET language_id = ?', $english['id']);
        }
    }
}
