<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Features;

use Angie\Features\Feature;

class ProjectTemplatesFeature extends Feature implements ProjectTemplatesFeatureInterface
{
    public function getName(): string
    {
        return ProjectTemplatesFeatureInterface::NAME;
    }

    public function getVerbose(): string
    {
        return ProjectTemplatesFeatureInterface::VERBOSE_NAME;
    }

    public function getAddOnsAvailableOn(): array
    {
        return [];
    }

    public function getIsEnabledFlag(): string
    {
        return 'project_templates_enabled';
    }

    public function getIsEnabledLockFlag(): string
    {
        return 'project_templates_enabled_lock';
    }
}
