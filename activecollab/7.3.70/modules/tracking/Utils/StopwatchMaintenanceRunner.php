<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Module\Tracking\Utils;

use ActiveCollab\Module\Tracking\Services\StopwatchServiceInterface;
use AngieApplication;
use DateTimeValue;
use DB;
use Exception;
use Psr\Log\LoggerInterface;
use Stopwatch;
use Stopwatches;
use Task;
use User;

class StopwatchMaintenanceRunner implements StopwatchMaintenanceRunnerInterface
{
    private LoggerInterface $logger;
    private StopwatchManagerInterface $manager;
    private StopwatchServiceInterface $stopwatch_service;

    public function __construct(
        StopwatchManagerInterface $manager,
        StopwatchServiceInterface $stopwatchService,
        LoggerInterface $logger
    )
    {
        $this->logger = $logger;
        $this->manager = $manager;
        $this->stopwatch_service = $stopwatchService;
    }

    public function run() {
        $account_id = AngieApplication::getAccountId();
        $performanceMonitor = new \Symfony\Component\Stopwatch\Stopwatch();
        $performanceMonitor->start('stopwatch-runner');
        $this->logger->info("Running stopwatch maintenance for account $account_id");
        /**
         * @var User
         * @var $stopwatches Stopwatches
         * @var $stopwatch   Stopwatch
         * @var $task        Task
         */
        $stopwatches = $this->manager->findStopwatchesForMaximumCapacity();
        $this->runMaximumCapacity($stopwatches);

        $global_user_daily_capacity = $this->manager->getGlobalUserDailyCapacity();

        $stopwatches = $this->manager->findStopwatchesForDailyCapacity($global_user_daily_capacity);
        $this->runDailyCapacity($stopwatches);
        $this->manager->updateStopwatchesMaximumReach();
        $info = $performanceMonitor->stop('stopwatch-runner');
        $this->logger->info(
            sprintf(
                'Finished stopwatch maintenance for account %s , %s',
                $account_id,
                $info->__toString()
            )
        );
    }

    protected function runDailyCapacity($stopwatches)
    {
        if($stopwatches && is_iterable($stopwatches)){
            try {
                DB::beginWork('Running daily capacity maintenance @ '.__CLASS__);
                /** @var Stopwatch $stopwatch */
                foreach ($stopwatches as $stopwatch) {
                    if(!$this->getRelatedData($stopwatch)){
                        continue;
                    }
                    $this->stopwatch_service
                        ->edit(
                            $stopwatch,
                            [
                                'notification_sent_at' => new DateTimeValue(),
                            ]
                        );
                    $this->manager
                        ->sendNotificationForDailyCapacity($stopwatch);
                }
                DB::commit('Finished daily capacity @ ' . __CLASS__);
            } catch (Exception $exception){
                DB::rollback('Rollback maximum capacity action @ '.__CLASS__);
                $this->logger->error('Failed to maintenance daily capacity', [
                    'line' => $exception->getLine(),
                    'file' => $exception->getFile(),
                    'message' => $exception->getMessage(),
                    'trace' => $exception->getTraceAsString(),
                ]);
            }
        }
    }

    protected function runMaximumCapacity($stopwatches)
    {
        if($stopwatches && is_iterable($stopwatches)){
            try {
                DB::beginWork('Running maximum capacity maintenance @ '.__CLASS__);
                /** @var Stopwatch $stopwatch */
                foreach ($stopwatches as $stopwatch) {
                    if(!$this->getRelatedData($stopwatch)){
                        continue;
                    }
                    $this->stopwatch_service
                        ->edit(
                            $stopwatch,
                            [
                                'started_on' => null,
                                'elapsed' => StopwatchServiceInterface::STOPWATCH_MAXIMUM,
                            ]
                        );

                    $this->manager->sendNotificationForMaximumReached($stopwatch);
                }
                DB::commit('Finished maximum capacity @ ' . __CLASS__);
            } catch (Exception $exception){
                DB::rollback('Rollback maximum capacity action @ '.__CLASS__);
                $this->logger->error('Failed to maintenance maximum capacity', [
                    'line' => $exception->getLine(),
                    'file' => $exception->getFile(),
                    'message' => $exception->getMessage(),
                    'trace' => $exception->getTraceAsString(),
                ]);
            }
        }
    }

    protected function getRelatedData(Stopwatch $stopwatch): bool
    {
        if(!$stopwatch->getUser()){
            return false;
        }

        if(!$stopwatch->getParent()){
            return false;
        }

        return true;
    }
}
