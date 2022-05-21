<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\SystemExport;

interface SystemExportInterface
{
    public function export($pack = true, $delete_work_folder = true): string;
}
