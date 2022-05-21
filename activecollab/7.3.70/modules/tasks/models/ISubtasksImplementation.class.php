<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

trait ISubtasksImplementation
{
    /**
     * @var array|null
     */
    private $after_save_set_subtasks;

    /**
     * Fields that are can be set for subtasks.
     *
     * @var array
     */
    private $require_fields = [
        'id',
        'assignee_id',
        'body',
    ];

    public function registerISubtasksImplementation(): void
    {
        $this->registerEventHandler(
            'on_json_serialize',
            function (array & $result) {
                $result['subtasks'] = $this->getSubtasks();
            }
        );

        $this->registerEventHandler(
            'on_set_attribute',
            function ($attribute, $value) {
                if ($attribute == 'subtasks' && is_array($value)) {
                    $this->after_save_set_subtasks = $value;
                }
            }
        );

        $this->registerEventHandler(
            'on_before_save',
            function () {
                if ($this->after_save_set_subtasks !== null && is_array($this->after_save_set_subtasks)) {
                    if (count($this->after_save_set_subtasks)) {
                        foreach ($this->after_save_set_subtasks as $k => $v) {
                            if (!empty($this->after_save_set_subtasks[$k]) && is_array($v)) {
                                // Clear not subtask fields
                                foreach (array_keys($v) as $subtask_key) {
                                    if (!in_array($subtask_key, $this->require_fields)) {
                                        unset($this->after_save_set_subtasks[$k][$subtask_key]);
                                    }
                                }
                            } else {
                                // Clear empty values
                                unset($this->after_save_set_subtasks[$k]);
                            }
                        }
                    }

                    $this->setSubtasks($this->after_save_set_subtasks);
                }
            }
        );
    }

    abstract protected function registerEventHandler(string $event, callable $handler): void;
    abstract public function getSubtasks(bool $include_trashed = false): ?iterable;
    abstract public function setSubtasks(?iterable $recurring_subtasks): ?iterable;
}
