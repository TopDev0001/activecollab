<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Command;

use ActivityLogs;
use Angie\Command\Command;
use DB;
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package Angie\Command
 */
final class RebuildActivityLogsCommand extends Command
{
    /**
     * Configure the command.
     */
    protected function configure()
    {
        parent::configure();

        $this->setDescription('Rebuild activity logs');
    }

    /**
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>OK:</info> Rebuilding activity logs');

        $microtime = microtime(true);

        try {
            ActivityLogs::clear();

            foreach (ActivityLogs::getRebuildActions() as $action_name => $action) {
                if (is_callable($action['callback'])) {
                    $output->writeln("<info>OK:</info> $action[label]");
                    call_user_func($action['callback']);
                } else {
                    return $this->abort("Rebuild action '$action_name' has no proper execution method", 1, $input, $output);
                }
            }

            DB::execute('UPDATE activity_logs SET updated_on = created_on WHERE updated_on IS NULL');
        } catch (Exception $e) {
            return $this->abortDueToException($e, $input, $output);
        }

        return $this->success('Done in ' . round(microtime(true) - $microtime, 2) . 's', $input, $output);
    }
}
