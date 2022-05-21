<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\ProjectExport;

interface ProjectExportInterface
{
    const EXPORT_ROUTINE_VERSION = '2.0';

    public function export(bool $delete_work_folder = true): string;
}
