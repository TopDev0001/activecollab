<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Command;

use Angie\Command\Command;
use DB;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearRoutingCacheCommand extends Command
{
    protected function configure()
    {
        parent::configure();

        $this->setDescription('Clear routing cache');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            DB::execute('TRUNCATE TABLE `routing_cache`');

            return $this->success('Done', $input, $output);
        } catch (\Exception $e) {
            return $this->abortDueToException($e, $input, $output);
        }
    }
}
