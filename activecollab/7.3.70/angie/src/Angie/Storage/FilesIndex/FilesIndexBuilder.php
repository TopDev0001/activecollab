<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Storage\FilesIndex;

use DB;
use ProjectTemplateFile;

class FilesIndexBuilder implements FilesIndexBuilderInterface
{
    public function getFilesIndex(array $types, bool $include_trashed_items = false): array
    {
        return array_merge(
            $this->getFiles($types, $include_trashed_items),
            $this->getAttachments($types),
            $this->getProjectTemplates($types)
        );
    }

    private function getFiles(array $types, bool $include_trashed_items): array
    {
        if ($include_trashed_items) {
            $file_names = DB::executeFirstColumn('SELECT `location` FROM `files` WHERE `type` IN (?)', $types);
        } else {
            $file_names = DB::executeFirstColumn(
                'SELECT `location` FROM `files` WHERE `type` IN (?) AND `is_trashed` = ?',
                $types,
                false
            );
        }

        if (empty($file_names)) {
            $file_names = [];
        }

        return $file_names;
    }

    private function getAttachments(array $types): array
    {
        $file_names = DB::executeFirstColumn('SELECT `location` FROM `attachments` WHERE `type` IN (?)', $types);

        if (empty($file_names)) {
            $file_names = [];
        }

        return $file_names;
    }

    private function getProjectTemplates(array $types): array
    {
        $result = [];

        if ($project_template_file_rows = DB::execute(
            'SELECT id, raw_additional_properties
                FROM project_template_elements
                WHERE `type` = ?',
            ProjectTemplateFile::class
        )) {
            foreach ($project_template_file_rows as $project_template_file_row) {
                $unserialized_properties = unserialize($project_template_file_row['raw_additional_properties']);

                if ($this->shouldIncludeProjectTemplateFile($unserialized_properties, $types)) {
                    $result[] = $unserialized_properties['location'];
                }
            }
        }

        return $result;
    }

    private function shouldIncludeProjectTemplateFile($unserialized_properties, array $types)
    {
        return !empty($unserialized_properties['type'])
            && !empty($unserialized_properties['location'])
            && in_array($unserialized_properties['type'], $types);
    }
}
