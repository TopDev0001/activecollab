<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Command;

use ActiveCollab\Module\Tracking\Utils\BudgetNotificationsMaintenanceRunnerInterface;
use Angie\Command\Command;
use AngieApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BudgetNotificationsMaintenanceCommand extends Command
{
    protected function configure()
    {
        parent::configure();

        $this->setDescription('Budget notifications maintenance at ActiveCollab');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        AngieApplication::getContainer()
            ->get(BudgetNotificationsMaintenanceRunnerInterface::class)
                ->run();

        return $this->success('Budget notifications maintenance done.', $input, $output);
    }
}
