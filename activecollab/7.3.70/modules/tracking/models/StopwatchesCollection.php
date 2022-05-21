<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class StopwatchesCollection extends CompositeCollection
{
    use IWhosAsking;

    private $tag;

    /**
     * @var Stopwatch[]
     */
    private $stopwatches;

    private $max_updated = 0;

    /**
     * Run the query and return DB result.
     *
     * @return DbResult|DataObject[]
     */
    public function execute()
    {
        $stopwatches = $this->getStopwatchesForUser($this->getWhosAsking());
        $tasks = [];
        $projects = [];
        if ($stopwatches) {
            $task_ids = [];
            $project_ids = [];

            foreach ($stopwatches as $stopwatch) {
                if ($stopwatch->getParentType() === Task::class) {
                    $task_ids[] = $stopwatch->getParentId();
                }
                if ($stopwatch->getParentType() === Project::class) {
                    $project_ids[] = $stopwatch->getParentId();
                }
            }

            $project_ids = array_merge($project_ids, Projects::getProjectIdsByTaskIds($task_ids));

            if (!empty($task_ids)) {
                $tasks = Tasks::findBy([
                    'id' => $task_ids,
                ]);
            }
            if (!empty($project_ids)) {
                $projects = Projects::findBy([
                    'id' => $project_ids,
                ]);
            }
        }

        return [
            'stopwatches' => $stopwatches,
            'tasks' => $tasks,
            'projects' => $projects,
        ];
    }

    /**
     * Return number of records that match conditions set by the collection.
     *
     * @return int
     */
    public function count()
    {
        if (!$this->stopwatches) {
            $this->getStopwatchesForUser($this->getWhosAsking());
        }

        return count($this->stopwatches);
    }

    public function getModelName(): string
    {
        return Stopwatches::class;
    }

    /**
     * Return collection etag.
     *
     * @param  bool   $use_cache
     * @return string
     */
    public function getTag(IUser $user, $use_cache = true)
    {
        if (!$this->tag || !$use_cache) {
            $this->tag = $this->prepareTagFromBits($user->getEmail(), $this->getTimestampHash($user));
        }

        return $this->tag;
    }

    private function getTimestampHash(IUser $user): string
    {
        if (!$this->max_updated){
            $updated = DB::executeFirstCell("SELECT GROUP_CONCAT(updated_on ORDER BY id SEPARATOR ',') AS hash FROM stopwatches WHERE user_id = ?", $user->getId()) ?? 0;
            $this->max_updated = sha1($updated);
        }

        return $this->max_updated;
    }

    private function getStopwatchesForUser(IUser $user)
    {
        if (!$user) {
            return null;
        }
        if (!$this->stopwatches) {
            $this->stopwatches = Stopwatches::findBy([
                'user_id' => $user->getId(),
            ]);
        }

        return $this->stopwatches;
    }
}
