<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\SubscriptionCleaner;

use ActiveCollab\Logger\LoggerInterface;
use ActiveCollab\Module\System\Events\Maintenance\DailyMaintenanceEvent;
use Angie\Inflector;
use CustomReminder;
use DB;
use Discussion;
use Exception;
use Note;
use RecurringTask;
use Task;

class SubscriptionCleaner implements SubscriptionCleanerInterface
{
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger
    )
    {
        $this->logger = $logger;
    }

    public function clean(): void
    {
        $project_ids = DB::executeFirstColumn('SELECT id FROM projects WHERE completed_on <= (NOW() - INTERVAL 90 DAY)');

        if (!$project_ids) {
            return;
        }

        try {
            DB::execute('DELETE s FROM subscriptions s
                            JOIN reminders r ON r.id = s.parent_id AND s.parent_type = ?
                            JOIN tasks t ON r.parent_type = ? AND r.parent_id = t.id
                            WHERE t.project_id IN (?)', CustomReminder::class, Task::class, $project_ids);

            $classes = [Task::class, Note::class, Discussion::class, RecurringTask::class];
            $query = "DELETE s FROM subscriptions s JOIN %s t ON s.parent_type = '%s' AND s.parent_id = t.id WHERE t.project_id IN (?)";

            foreach ($classes as $class) {
                DB::execute(sprintf($query, Inflector::pluralize(Inflector::underscore($class)), $class), $project_ids);
            }
        } catch (Exception $exception) {
            $this->logger->error("Failed to delete old subscriptions. Error: {$exception->getMessage()}");
        }
    }

    public function __invoke(DailyMaintenanceEvent $event)
    {
        $this->clean();
    }
}
