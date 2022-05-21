<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Command;

abstract class SampleProjectCommand extends Command
{
    protected function getCommandNamePrefix(): string
    {
        return 'sample_project:';
    }
}
