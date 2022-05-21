<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate labels model.
 *
 * @package angie.migrations
 */
class MigrateUpdateLabelsModel extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $labels = $this->useTableForAlter('labels');

        $labels->addColumn(DBStringColumn::create('foreground_color', 50), 'name');
        $labels->addColumn(DBStringColumn::create('background_color', 50), 'foreground_color');
        $labels->addColumn(new DBUpdatedOnColumn(), 'background_color');
        $labels->addColumn(DBIntegerColumn::create('position', DBColumn::NORMAL, 0)->setUnsigned(true), 'is_default');

        if ($rows = $this->execute('SELECT id, raw_additional_properties FROM ' . $labels->getName())) {
            $this->execute('UPDATE ' . $labels->getName() . ' SET updated_on = UTC_TIMESTAMP()');

            foreach ($rows as $row) {
                $properties = $row['raw_additional_properties'] ? unserialize($row['raw_additional_properties']) : null;

                if ($properties && is_array($properties)) {
                    $foreground_color = isset($properties['fg_color']) && $properties['fg_color'] ? $properties['fg_color'] : null;
                    $background_color = isset($properties['bg_color']) && $properties['bg_color'] ? $properties['bg_color'] : null;

                    if ($foreground_color || $background_color) {
                        $this->execute('UPDATE ' . $labels->getName() . ' SET foreground_color = ?, background_color = ? WHERE id = ?', $foreground_color, $background_color, $row['id']);
                    }
                }
            }
        }

        $labels->dropColumn('raw_additional_properties');
        $labels->addIndex(DBIndex::create('label_name', DBIndex::UNIQUE, ['type', 'name']));

        $this->execute('UPDATE ' . $labels->getName() . ' SET type = ? WHERE type = ?', 'TaskLabel', 'AssignmentLabel');
    }
}
