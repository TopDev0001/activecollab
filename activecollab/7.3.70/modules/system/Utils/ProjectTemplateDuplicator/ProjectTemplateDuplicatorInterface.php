<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\ProjectTemplateDuplicator;

use ProjectTemplate;
use User;

interface ProjectTemplateDuplicatorInterface
{
    public function duplicate(ProjectTemplate $template, User $by, string $new_template_name = null): ProjectTemplate;
}
