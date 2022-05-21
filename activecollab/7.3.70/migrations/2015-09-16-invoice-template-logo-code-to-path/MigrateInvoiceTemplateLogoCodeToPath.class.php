<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Change invoce template logo code to path.
 *
 * @package activeCollab.modules.system
 */
class MigrateInvoiceTemplateLogoCodeToPath extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        [$config_options, $uploaded_files] = $this->useTables('config_options', 'uploaded_files');

        $config_option_name = 'invoice_template';

        // get invoice_template config option value
        if ($value = $this->executeFirstCell("SELECT value FROM $config_options WHERE name = ?", $config_option_name)) {
            // unzerialize it
            $attributes = unserialize($value);

            // find row in uploded_files table by uploaded_logo_code value
            $row = $this->executeFirstRow("SELECT id, location, created_on FROM $uploaded_files WHERE code = ?", array_var($attributes, 'uploaded_logo_code'));

            // set logo_path and logo_timestamp attributes
            $attributes['logo_path'] = $row ? $row['location'] : null;
            $attributes['logo_timestamp'] = $row ? strtotime($row['created_on']) : 0;

            // remove uploaded_logo_code from attributes
            unset($attributes['uploaded_logo_code']);

            // delete row in uploaded_files table if exist
            if ($row) {
                $this->execute("DELETE FROM $uploaded_files WHERE id = ?", $row['id']);
            }

            // serialize new invoice_template value and save it to config_options table
            $this->execute("UPDATE $config_options SET value = ? WHERE name = ?", serialize($attributes), $config_option_name);
        }
    }
}
