<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\ProjectExport\Exporter;

use ActiveCollab\Module\System\Utils\ProjectExport\ProjectExport;
use AngieApplication;
use Client;

class ProjectHtmlExporter extends ProjectExport
{
    public function export(bool $delete_work_folder = true): string
    {
        $file_path = $this->getFilePath();

        if (!is_file($file_path)) {
            $this->prepareWorkFolder($this->getWorkFolderPath());

            $this->pack($this->getWorkFolderPath(), $file_path, $delete_work_folder);
        }

        return $file_path;
    }

    protected function getWorkFolderName(): string
    {
        return sprintf(
            '%d-project-html-%d-for-%s-%d',
            AngieApplication::getAccountId(),
            $this->project->getId(),
            $this->user instanceof Client ? 'client' : 'member',
            $this->project->getUpdatedOn()->getTimestamp()
        );
    }
}
