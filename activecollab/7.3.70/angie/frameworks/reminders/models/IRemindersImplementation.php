<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

trait IRemindersImplementation
{
    public function registerIRemindersImplementation(): void
    {
        $this->registerEventHandler('on_before_delete', function () {
            if ($reminders = $this->getReminders()) {
                foreach ($reminders as $reminder) {
                    $reminder->delete(true);
                }
            }
        });

        $this->registerEventHandler('on_describe_single', function (array & $result) {
            $result['reminders'] = DB::executeFirstColumn('SELECT DISTINCT created_by_id FROM reminders WHERE parent_type = ? AND parent_id = ?', get_class($this), $this->getId());

            if (empty($result['reminders'])) {
                $result['reminders'] = [];
            }
        });
    }

    abstract protected function registerEventHandler(string $event, callable $handler): void;

    /**
     * Return reminders.
     *
     * @return Reminder[]|null
     */
    public function getReminders()
    {
        return Reminders::find([
            'conditions' => ['parent_type = ? AND parent_id = ?', get_class($this), $this->getId()],
        ]);
    }

    /**
     * @return int
     */
    abstract public function getId();
}
