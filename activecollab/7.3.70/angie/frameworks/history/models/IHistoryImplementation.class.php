<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\History\Renderers\HistoryFieldRendererInterface;
use ActiveCollab\Module\System\Model\Categories\ICategory;
use Angie\Events;

trait IHistoryImplementation
{
    /**
     * Array of fiels that we want to track changes to.
     */
    private array $history_fields = [];

    public function registerIHistoryImplementation()
    {
        $this->registerEventHandler(
            'on_after_save',
            function ($is_new, $modifications) {
                if ($is_new) {
                    return;
                }

                if (empty($modifications)) {
                    $modifications = [];
                }

                $by = AngieApplication::authentication()->getAuthenticatedUser();

                if ($by instanceof IUser) {
                    $this->logModifications($modifications, $by);
                }
            }
        );

        $this->registerEventHandler(
            'on_before_delete',
            function () {
                ModificationLogs::deleteByParent($this);
            }
        );

        foreach (['type', 'name', 'state', 'visibility'] as $common_field) {
            if ($this->fieldExists($common_field)) {
                $this->history_fields[] = $common_field;
            }
        }

        if ($this instanceof IComplete) {
            $this->history_fields[] = 'priority';
            $this->history_fields[] = 'due_on';
            $this->history_fields[] = 'completed_on';
        }

        if ($this instanceof ILabel) {
            $this->history_fields[] = 'label_id';
        }

        if ($this instanceof ICategory) {
            $this->history_fields[] = 'category_id';
        }

        if ($this instanceof IAssignees) {
            $this->history_fields[] = 'assignee_id';
        }
    }

    public function getHistory(User $user): array
    {
        $result = [];

        $modification_ids = DB::executeFirstColumn(
            sprintf('SELECT id FROM modification_logs WHERE %s', ModificationLogs::parentToCondition($this))
        );

        if (!empty($modification_ids)) {
            $modification_logs = ModificationLogs::find(
                [
                    'conditions' => [
                        'id IN (?)',
                        $modification_ids,
                    ],
                ]
            );

            foreach ($modification_logs as $log) {
                $result[$log->getId()] = [
                    'timestamp' => $log->getCreatedOn(),
                    'created_by_id' => $log->getCreatedById(),
                    'created_by_name' => $log->getCreatedByName(),
                    'created_by_email' => $log->getCreatedByEmail(),
                    'modifications' => [],
                ];
            }

            $log_values = DB::execute(
                'SELECT * FROM modification_log_values WHERE modification_id IN (?) ORDER BY modification_id',
                $modification_ids
            );

            if ($log_values) {
                foreach ($log_values as $log_value) {
                    if (!$user->canSeeFieldHistory($this, $log_value['field'])) {
                        continue;
                    }

                    $result[$log_value['modification_id']]['modifications'][$log_value['field']] = [
                        unserialize($log_value['old_value']),
                        unserialize($log_value['new_value']),
                    ];
                }
            }
        }

        foreach (array_keys($result) as $modification_log_id) {
            if (empty($result[$modification_log_id]['modifications'])) {
                unset($result[$modification_log_id]);
            }
        }

        return array_values($result);
    }

    public function getVerboseHistory(User $user): array
    {
        $language = $user->getLanguage();
        $history = $this->getHistory($user);

        if (!empty($history)) {
            $renderers = $this->getHistoryFieldRenderers();

            foreach ($history as $k => $history_entry) {
                foreach ($history_entry['modifications'] as $field => $values) {
                    $history[$k]['modifications'][$field][2] = (string) $this->renderVerboseHistoryMessage(
                        $user,
                        $renderers,
                        $field,
                        $values[0],
                        $values[1],
                        $language
                    );
                }
            }
        }

        return array_values($history);
    }

    private function renderVerboseHistoryMessage(
        User $user,
        array $renderers,
        string $field,
        $old_value,
        $new_value,
        Language $language
    ): ?string
    {
        $history_field_renderer = $renderers[$field] ?? null;

        if ($history_field_renderer instanceof HistoryFieldRendererInterface) {
            return $history_field_renderer->render(
                $old_value,
                $new_value,
                $language
            );
        } elseif (is_callable($history_field_renderer)) {
            return call_user_func(
                $history_field_renderer,
                $old_value,
                $new_value,
                $language
            );
        }

        if (!empty($new_value) && !empty($old_value)) {
            return lang(
                ':field changed from :old_value to :new_value',
                [
                    'field' => $field,
                    'old_value' => $this->prepareLogValueForDisplay($old_value),
                    'new_value' => $this->prepareLogValueForDisplay($new_value),
                ],
                true,
                $language
            );
        } elseif ($new_value) {
            return lang(
                ':field set to :new_value',
                [
                    'field' => $field,
                    'new_value' => $this->prepareLogValueForDisplay($new_value),
                ],
                true,
                $language
            );
        } elseif ($old_value) {
            return lang(
                ':field set to empty value',
                [
                    'field' => $field,
                ],
                true,
                $language
            );
        }

        return null;
    }

    private function prepareLogValueForDisplay($value): string
    {
        return is_array($value) ? implode(', ', $value) : (string) $value;
    }

    public function getHistoryFields(): array
    {
        return $this->history_fields;
    }

    public function addHistoryFields(string ...$field_names): void
    {
        foreach ($field_names as $field_name) {
            if ($field_name && !in_array($field_name, $this->history_fields)) {
                $this->history_fields[] = $field_name;
            }
        }
    }

    public function getHistoryFieldRenderers(): array
    {
        $result['name'] = function ($old_value, $new_value, Language $language) {
            return lang('Name changed from <b>:old_value</b> to <b>:new_value</b>', ['old_value' => $old_value, 'new_value' => $new_value], true, $language);
        };

        $result['completed_on'] = function ($old_value, $new_value, Language $language) {
            $new_completed_on = $new_value ? new DateTimeValue($new_value) : null;

            if ($new_completed_on instanceof DateTimeValue && $new_completed_on->getTimestamp() > 0) {
                return lang('Marked as completed', null, true, $language);
            }

            return lang('Marked as open', null, true, $language);
        };

        $result['language_id'] = function ($old_value, $new_value, Language $language) {
            $lang = Languages::findById($new_value);
            $new_language = lang('default');
            if ($lang instanceof Language) {
                $new_language = $lang->getName();
            }
            $lang = Languages::findById($old_value);
            $old_language = lang('default');
            if ($lang instanceof Language) {
                $old_language = $lang->getName();
            }

            return lang(
                'Language changed from <b>:old_value</b> to <b>:new_value</b>',
                [
                    'old_value' => $old_language,
                    'new_value' => $new_language,
                ],
                true,
                $language
            );
        };

        $this->triggerEvent('on_history_field_renderers', [&$result]);

        Events::trigger(
            'on_history_field_renderers',
            [
                $this,
                &$result,
            ]
        );

        return $result;
    }

    /**
     * Return latest modification entry.
     *
     * @return ModificationLog|DataObject
     */
    public function getLatestModification(): ?ModificationLog
    {
        return ModificationLogs::findOne(
            [
                'conditions' => ['`parent_type` = ? AND `parent_id` = ?', get_class($this), $this->getId()],
                'order' => '`created_on` DESC',
            ]
        );
    }

    /**
     * Commit object modifications.
     */
    private function logModifications(array $modifications, IUser $by): void
    {
        $track_fields = $this->getHistoryFields();

        $field_values_to_log = [];
        foreach ($modifications as $field => $value) {
            if (in_array($field, $track_fields) && is_array($value) && count($value) == 2) {
                $field_values_to_log[] = $field;
            }
        }

        $additional_modifications_to_log = [];

        $this->triggerEvent(
            'on_additional_modifications',
            [
                &$additional_modifications_to_log,
            ]
        );

        if (!empty($field_values_to_log) || !empty($additional_modifications_to_log)) {
            DB::transact(
                function () use ($modifications, $by, $field_values_to_log, $additional_modifications_to_log) {
                    DB::execute(
                        'INSERT INTO
                            `modification_logs` (
                                `parent_type`,
                                `parent_id`,
                                `created_on`,
                                `created_by_id`,
                                `created_by_name`,
                                `created_by_email`
                            ) VALUES (
                                ?,
                                ?,
                                ?,
                                ?,
                                ?,
                                ?
                            )',
                        get_class($this),
                        $this->getId(),
                        DateTimeValue::now(),
                        $by->getId(),
                        $by->getName(),
                        $by->getEmail()
                    );

                    $log_id = DB::lastInsertId();

                    $batch = new DBBatchInsert(
                        'modification_log_values',
                        [
                            'modification_id',
                            'field',
                            'old_value',
                            'new_value',
                        ]
                    );

                    foreach ($field_values_to_log as $field) {
                        [
                            $old_value,
                            $new_value,
                        ] = $modifications[$field];

                        if ($old_value instanceof DateValue) {
                            $old_value = $old_value->toMySQL();
                        }

                        if ($new_value instanceof DateValue) {
                            $new_value = $new_value->toMySQL();
                        }

                        $batch->insert($log_id, $field, serialize($old_value), serialize($new_value));
                    }

                    foreach ($additional_modifications_to_log as $additional_field => $old_and_new_value) {
                        [
                            $old_value,
                            $new_value,
                        ] = $old_and_new_value;

                        if ($old_value instanceof DateValue) {
                            $old_value = $old_value->toMySQL();
                        }

                        if ($new_value instanceof DateValue) {
                            $new_value = $new_value->toMySQL();
                        }

                        $batch->insert($log_id, $additional_field, serialize($old_value), serialize($new_value));
                    }

                    $batch->done();
                },
                'Commit object modification'
            );
        }
    }

    // ---------------------------------------------------
    //  Expectations
    // ---------------------------------------------------

    /**
     * Return object ID.
     *
     * @return string
     */
    abstract public function getId();

    /**
     * Check if specific key is defined.
     *
     * @param  string $field Field name
     * @return bool
     */
    abstract public function fieldExists($field);

    /**
     * Return value of specific field and typecast it...
     *
     * @param  string $field   Field value
     * @param  mixed  $default Default value that is returned in case of any error
     * @return mixed
     */
    abstract public function getFieldValue($field, $default = null);

    abstract protected function registerEventHandler(string $event, callable $handler): void;

    /**
     * Trigger an internal event.
     *
     * @param string $event
     * @param array  $event_parameters
     */
    abstract protected function triggerEvent($event, $event_parameters = null);
}
