<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Update task label label colors.
 *
 * @package activecollab.migrations
 */
class MigrateTaskLabelColors extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $label_colors = [
            'NEW' => '#00B25C',
            'CONFIRMED' => '#F26522',
            'WORKS FOR ME' => '#00B25C',
            'DUPLICATE' => '#00B25C',
            'WONT FIX' => '#00B25C',
            'ASSIGNED' => '#FF0000',
            'BLOCKED' => '#ACACAC',
            'IN PROGRESS' => '#00B25C',
            'FIXED' => '#0000FF',
            'REOPENED' => '#FF0000',
            'VERIFIED' => '#00B25C',
        ];

        $labels = $this->useTables('labels')[0];

        foreach ($label_colors as $label => $color) {
            $this->execute("UPDATE $labels SET color = ? WHERE type = 'TaskLabel' AND (name = ? OR name = ?)", $color, $label, str_replace(' ', '', $label));
        }

        $this->doneUsingTables();
    }
}
