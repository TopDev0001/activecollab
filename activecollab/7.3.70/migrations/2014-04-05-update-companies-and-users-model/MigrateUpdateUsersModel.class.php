<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate users model.
 *
 * @package ActiveCollab.migrations
 */
class MigrateUpdateUsersModel extends AngieModelMigration
{
    /**
     * Construct migration.
     */
    public function __construct()
    {
        $this->executeAfter('MigrateUpdateCompaniesModel');
    }

    /**
     * Update companies model.
     */
    public function up()
    {
        $users = $this->useTableForAlter('users');

        if ($users->getColumn('is_trashed')) {
            $users->dropColumn('is_trashed'); // Drop is_trashed if exists (probably added by a third party module)
        }

        $users->addColumn(
            new DBBoolColumn('is_archived'),
            'original_state'
        );
        $users->addColumn(
            new DBBoolColumn('original_is_archived'),
            'is_archived'
        );

        $users->addColumn(
            new DBBoolColumn('is_trashed'),
            'original_is_archived'
        );
        $users->addColumn(
            new DBBoolColumn('original_is_trashed'),
            'is_trashed'
        );
        $users->addColumn(
            new DBDateTimeColumn('trashed_on'),
            'is_trashed'
        );
        $users->addColumn(
            DBFkColumn::create('trashed_by_id'),
            'trashed_on'
        );
        $users->addIndex(DBIndex::create('trashed_by_id'));

        $this->execute('UPDATE ' . $users->getName() . ' SET created_on = UTC_TIMESTAMP() WHERE created_on IS NULL');
        $this->execute('UPDATE ' . $users->getName() . ' SET updated_on = UTC_TIMESTAMP() WHERE updated_on IS NULL');

        defined('STATE_TRASHED') or define('STATE_TRASHED', 1);
        defined('STATE_ARCHIVED') or define('STATE_ARCHIVED', 2);

        $this->execute('UPDATE ' . $users->getName() . ' SET is_archived = ? WHERE state = ?', true, STATE_ARCHIVED);
        $this->execute('UPDATE ' . $users->getName() . ' SET is_archived = ?, is_trashed = ?, trashed_on = NOW() WHERE state = ? AND original_state = ?', true, true, STATE_TRASHED, STATE_ARCHIVED);
        $this->execute('UPDATE ' . $users->getName() . ' SET is_trashed = ?, trashed_on = NOW() WHERE state = ? AND is_trashed = ?', true, STATE_TRASHED, false);

        $users->dropColumn('state');
        $users->dropColumn('original_state');

        $users->addColumn(DBStringColumn::create('title'), 'last_name');
        $users->addColumn(DBStringColumn::create('phone'), 'email');
        $users->addColumn(DBStringColumn::create('im_type'), 'phone');
        $users->addColumn(DBStringColumn::create('im_handle'), 'im_type');
        $users->addColumn(new DBTextColumn('note'), 'note');

        $users->dropColumn('auto_assign_role_id');
        $users->dropColumn('auto_assign_permissions');

        [$users, $config_options, $config_option_values] = $this->useTables('users', 'config_options', 'config_option_values');

        if ($rows = $this->execute("SELECT name, parent_id, value FROM $config_option_values WHERE parent_type IN ('Administrator', 'Manager', 'Member', 'Subcontractor', 'Client', 'User') AND name IN ('title', 'phone_mobile', 'phone_work', 'im_type', 'im_value')")) {
            $user_data = [];

            /*
             * Unserialize value
             *
             * @param string $v
             * @return string
             */
            $unserialize_value = function ($v) {
                return $v && str_starts_with($v, 's:') ? trim(unserialize($v)) : trim($v);
            };

            foreach ($rows as $row) {
                $user_id = $row['parent_id'];

                if (empty($user_data[$user_id])) {
                    $user_data[$user_id] = ['title' => null, 'phone_mobile' => null, 'phone_work' => null, 'im_type' => null, 'im_value' => null];
                }

                switch ($row['name']) {
                    case 'title':
                        $user_data[$user_id]['title'] = $unserialize_value($row['value']);
                        break;
                    case 'phone_mobile':
                        $user_data[$user_id]['phone_mobile'] = $unserialize_value($row['value']);
                        break;
                    case 'phone_work':
                        $user_data[$user_id]['phone_work'] = $unserialize_value($row['value']);
                        break;
                    case 'im_type':
                        $user_data[$user_id]['im_type'] = $unserialize_value($row['value']);
                        break;
                    case 'im_value':
                        $user_data[$user_id]['im_value'] = $unserialize_value($row['value']);
                        break;
                }
            }

            foreach ($user_data as $user_id => $user) {
                $phone = null;
                $im_type = null;
                $im_value = null;
                $note = '';

                if ($user['phone_mobile'] && $user['phone_work']) {
                    $phone = $user['phone_work'];

                    $note = 'Mobile Phone #: ' . $user['phone_mobile'];
                } elseif ($user['phone_work']) {
                    $phone = $user['phone_work'];
                } elseif ($user['phone_mobile']) {
                    $phone = $user['phone_mobile'];
                }

                if ($user['im_type'] && $user['im_value']) {
                    $im_type = $user['im_type'];
                    $im_value = $user['im_value'];
                }

                $this->execute("UPDATE $users SET title = ?, phone = ?, im_type = ?, im_handle = ?, note = ? WHERE id = ?", $user['title'], $phone, $im_type, $im_value, $note, $user_id);
            }
        }

        $this->execute("DELETE FROM $config_options WHERE name IN ('title', 'phone_mobile', 'phone_work', 'im_type', 'im_value')");
        $this->execute("DELETE FROM $config_option_values WHERE name IN ('title', 'phone_mobile', 'phone_work', 'im_type', 'im_value')");

        $this->doneUsingTables();
    }
}
