<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Drop label_id modifications for tasks.
 *
 * @package ActiveCollab.migrations
 */
class MigrateDropLabelModificationsForTasks extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        [$modification_log, $modification_log_values] = $this->useTables('modification_logs', 'modification_log_values');

        if ($modification_ids = $this->executeFirstColumn("SELECT id FROM $modification_log AS ml LEFT JOIN $modification_log_values AS mlv ON ml.id = mlv.modification_id WHERE ml.parent_type = 'Task' AND mlv.field = 'label_id'")) {
            $this->execute("DELETE FROM $modification_log_values WHERE modification_id IN (?) AND field = 'label_id'", $modification_ids);
            $this->execute("DELETE FROM $modification_log WHERE NOT EXISTS (SELECT * FROM $modification_log_values WHERE $modification_log_values.modification_id = $modification_log.id)");
        }

        $this->doneUsingTables();
    }
}
