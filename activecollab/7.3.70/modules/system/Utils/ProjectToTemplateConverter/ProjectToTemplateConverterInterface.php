<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\ProjectToTemplateConverter;

use Project;
use ProjectTemplate;

interface ProjectToTemplateConverterInterface
{
    public function convertProjectToTemplate(
        Project $project,
        string $template_name = null
    ): ProjectTemplate;
}
