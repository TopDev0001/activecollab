<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateUpdateProjectTemplateFiles extends AngieModelMigration
{
    public function up()
    {
        $project_template_elements = $this->useTables('project_template_elements')[0];

        if ($rows = $this->execute("SELECT id, raw_additional_properties FROM $project_template_elements WHERE type = ?", 'ProjectTemplateFile')) {
            foreach ($rows as $row) {
                $id = $row['id'];
                $data = unserialize($row['raw_additional_properties']);

                if (isset($data['type']) && $data['type'] === 'File') {
                    if (empty($data['md5'])) {
                        $data['md5'] = md5_file(AngieApplication::fileLocationToPath($data['location']));
                    }
                    $data['type'] = 'LocalFile';
                    $this->execute("UPDATE $project_template_elements SET raw_additional_properties = ? WHERE id = ?", serialize($data), $id);
                }
            }
        }
    }
}
