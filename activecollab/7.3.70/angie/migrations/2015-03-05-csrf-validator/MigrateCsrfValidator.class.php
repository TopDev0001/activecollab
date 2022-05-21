<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add CSRF validator field to API subscriptions model.
 *
 * @package angie.migrations
 */
class MigrateCsrfValidator extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $api_subscriptions = $this->useTableForAlter('api_subscriptions');

        $this->execute('DELETE FROM ' . $api_subscriptions->getName() . ' WHERE lifetime > ?', 0);

        $api_subscriptions->addColumn(DBStringColumn::create('csrf_validator', 40), 'token');

        $csrf_validators = [];

        if ($rows = $this->execute('SELECT id FROM ' . $api_subscriptions->getName())) {
            foreach ($rows as $row) {
                do {
                    $csrf_validator = make_string(40);
                } while (in_array($csrf_validator, $csrf_validators));

                $this->execute('UPDATE ' . $api_subscriptions->getName() . ' SET csrf_validator = ? WHERE id = ?', $csrf_validator, $row['id']);

                $csrf_validators[] = $csrf_validator;
            }
        }

        $api_subscriptions->addIndex(DBIndex::create('csrf_validator', DBIndex::UNIQUE));

        $this->doneUsingTables();
    }
}
