<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Command;

use Symfony\Component\Console\Input\InputOption;

abstract class OnDemandCommand extends Command
{
    protected function configure()
    {
        parent::configure();

        $this
            ->addOption(
                'log-to-file',
                '',
                InputOption::VALUE_OPTIONAL,
                'Shepherd compatibility, should be removed'
            );
    }

    protected function getCommandNamePrefix(): string
    {
        return 'ondemand:';
    }
}
