<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddDefaultAvailabilityTypes extends AngieModelMigration
{
    public function up()
    {
        [$availability_types_table] = $this->useTables('availability_types');

        $default_availability_types = [
            'Day Off',
            'Vacation',
            'Sick Leave',
        ];

        $existing_names = DB::executeFirstColumn(
            sprintf('SELECT `name` FROM `%s`', $availability_types_table)
        );

        if (!empty($existing_names)) {
            $this->transact(
                function () use ($default_availability_types, $availability_types_table) {
                    foreach ($default_availability_types as $name) {
                        if (!in_array($name, $existing_names)) {
                            DB::execute(
                                "INSERT INTO $availability_types_table (name, level, created_on) VALUES (?, ?, ?)",
                                $name,
                                'not_available',
                                DateTimeValue::now()->toMySql()
                            );
                        }
                    }
                }
            );
        }

        $this->doneUsingTables();
    }
}
