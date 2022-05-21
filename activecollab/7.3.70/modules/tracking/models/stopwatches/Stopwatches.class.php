<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\Tracking\Services\StopwatchServiceInterface;

class Stopwatches extends BaseStopwatches
{
    public static function prepareCollection(string $collection_name, $user)
    {
        $collection = parent::prepareCollection($collection_name, $user);

        if (str_starts_with($collection_name, 'user_stopwatches')) {
            return (new StopwatchesCollection($collection_name))
                ->setWhosAsking($user);
        }

        return $collection;
    }

    public static function deleteByProject(Project $project): void
    {
        self::deleteByParent(Project::class, $project->getId());
    }

    public static function deleteByTask(Task $task): void
    {
        self::deleteByParent(Task::class, $task->getId());
    }

    private static function deleteByParent(string $parent_type, int $parent_id): void
    {
        $stopwatches = Stopwatches::find(
            [
                'conditions' => [
                    'parent_type = ? AND parent_id = ?',
                    $parent_type,
                    $parent_id,
                ],
            ]
        );

        if ($stopwatches) {
            /** @var StopwatchServiceInterface $stopwatch_service */
            $stopwatch_service = AngieApplication::getContainer()->get(StopwatchServiceInterface::class);

            foreach ($stopwatches as $stopwatch) {
                $stopwatch_service->delete($stopwatch);
            }
        }
    }
}
