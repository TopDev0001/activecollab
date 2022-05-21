<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Command;

use Angie\Command\Command;
use CronIntegration;
use Exception;
use Integrations;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package Angie\Command
 */
final class HourlyCommand extends Command
{
    protected function configure()
    {
        parent::configure();

        $this->setDescription('Run hourly maintenance (same as /tasks/cron_jobs/run_every_hour.php)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            /** @var CronIntegration $integration */
            $integration = Integrations::findFirstByType(CronIntegration::class);
            $integration->runEveryHour(time(), function ($message) use ($output) {
                $output->writeln('<info>OK:</info> ' . $message);
            });
        } catch (Exception $e) {
            return $this->abortDueToException($e, $input, $output);
        }

        return $this->success("Done in {$this->getExecutionTime()} seconds", $input, $output);
    }

    /**
     * @return float
     */
    private function getExecutionTime()
    {
        return round(microtime(true) - ANGIE_SCRIPT_TIME, 5);
    }
}
