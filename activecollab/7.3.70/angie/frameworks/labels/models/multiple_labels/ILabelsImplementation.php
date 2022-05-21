<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\History\Renderers\LabelsHistoryFieldRenderer;

trait ILabelsImplementation
{
    private ?array $labels_attribute_value = null;
    private ?array $before_update_label_ids = null;

    public function registerILabelsImplementation(): void
    {
        $this->registerEventHandler(
            'on_json_serialize',
            function (array &$result) {
                $result['labels'] = $this->getLabelDetails();
            },
        );

        $this->registerEventHandler(
            'on_set_attribute',
            function ($attribute, $value) {
                if ($attribute == 'labels' && is_array($value)) {
                    $this->labels_attribute_value = $this->prepareLabelsAttributeValue($value);
                }
            },
        );

        // Initial set of labels, no need to track anything for modification log.
        $this->registerEventHandler(
            'on_after_save',
            function ($is_new) {
                if ($is_new && $this->labels_attribute_value !== null && is_array($this->labels_attribute_value)) {
                    $this->saveLabelsFromAttribute($this->labels_attribute_value);
                }
            },
        );

        // Update before save for loaded objects, so modifications log can collect changes to the labels.
        $this->registerEventHandler(
            'on_before_save',
            function ($is_new) {
                if ($is_new) {
                    return;
                }

                if ($this->labels_attribute_value !== null && is_array($this->labels_attribute_value)) {
                    try {
                        DB::beginWork();

                        $this->before_update_label_ids = $this->clearLabels();
                        $new_label_added = $this->saveLabelsFromAttribute($this->labels_attribute_value);

                        AngieApplication::cache()->removeByObject($this);

                        // update project when new label is added on project
                        if ($this instanceof IProjectElement && $new_label_added) {
                            $this->getProject()->touch();
                        }

                        DB::commit();
                    } catch (Exception $e) {
                        DB::rollback();

                        throw $e;
                    }
                }
            },
        );

        $this->registerEventHandler(
            'on_additional_modifications',
            function (array &$additional_modifications_to_log) {
                if (is_array($this->before_update_label_ids)) {
                    $label_ids = $this->getLabelIds();

                    if ($this->before_update_label_ids !== $label_ids) {
                        $additional_modifications_to_log['labels'] = [
                            $this->before_update_label_ids,
                            $label_ids,
                        ];
                    }
                }
            },
        );

        $this->registerEventHandler(
            'on_history_field_renderers',
            function (&$renderers) {
                $renderers['labels'] = new LabelsHistoryFieldRenderer(
                    function (array $label_ids) {
                        return Labels::getNamesByIds($label_ids);
                    },
                );
            },
        );
    }

    /**
     * Return label details (id, name and color).
     *
     * @param  bool  $use_cache
     * @return array
     */
    private function getLabelDetails($use_cache = true)
    {
        return AngieApplication::cache()->getByObject(
            $this,
            'label_details',
            function () {
                return Labels::getDetailsByParent($this);
            },
            !$use_cache,
        );
    }

    /**
     * Return object labels.
     *
     * @return DBResult|Label[]
     */
    public function getLabels(): ?iterable
    {
        return Labels::findBySQL(
            'SELECT `labels`.*
                FROM `labels` LEFT JOIN `parents_labels` ON `labels`.`id` = `parents_labels`.`label_id`
                WHERE `parents_labels`.`parent_type` = ? AND `parents_labels`.`parent_id` = ?
                ORDER BY `labels`.`name`',
            get_class($this),
            $this->getId(),
        );
    }

    public function getLabelIds(): array
    {
        return $this->getLabelProperty('id');
    }

    public function getLabelNames(): array
    {
        return array_map(
            'mb_strtoupper',
            $this->getLabelProperty('name'),
        );
    }

    private function getLabelProperty(string $property_name): array
    {
        $result = DB::executeFirstColumn(
            sprintf(
                'SELECT `labels`.`%s`
                    FROM `labels` LEFT JOIN `parents_labels` ON `labels`.`id` = `parents_labels`.`label_id`
                    WHERE `parents_labels`.`parent_type` = ? AND `parents_labels`.`parent_id` = ?
                    ORDER BY `labels`.`%s`',
                $property_name,
                $property_name,
            ),
            get_class($this),
            $this->getId(),
        );

        if (empty($result)) {
            $result = [];
        }

        return $result;
    }

    public function countLabels(): int
    {
        return (int) DB::executeFirstCell(
            'SELECT COUNT(`labels`.`id`) AS "row_count"
                FROM `labels` LEFT JOIN `parents_labels` ON `labels`.`id` = `parents_labels`.`label_id`
                WHERE `parents_labels`.`parent_type` = ? AND `parents_labels`.`parent_id` = ?
                ORDER BY `labels`.`name`',
            get_class($this),
            $this->getId(),
        );
    }

    public function clearLabels(): array
    {
        $label_ids = DB::executeFirstColumn(
            'SELECT `label_id`
                FROM `parents_labels`
                WHERE `parent_type` = ? AND `parent_id` = ?
                ORDER BY `label_id`',
            get_class($this),
            $this->getId(),
        );

        if (!empty($label_ids)) {
            DB::execute(
                'DELETE FROM `parents_labels` WHERE `parent_type` = ? AND `parent_id` = ?',
                get_class($this),
                $this->getId(),
            );
            DB::execute('UPDATE `labels` SET `updated_on` = ? WHERE `id` IN (?)', DateTimeValue::now(), $label_ids);

            Labels::clearCacheFor($label_ids);

            AngieApplication::cache()->removeByObject($this);
            AngieApplication::invalidateInitialSettingsCache();

            return $label_ids;
        }

        return [];
    }

    /**
     * @param DataObject|ILabels $to
     */
    public function cloneLabelsTo(ILabels $to): ILabels
    {
        $label_ids = DB::executeFirstColumn(
            'SELECT label_id FROM parents_labels WHERE parent_type = ? AND parent_id = ?',
            get_class($this),
            $this->getId(),
        );

        if ($label_ids) {
            $batch = new DBBatchInsert(
                'parents_labels',
                [
                    'parent_type',
                    'parent_id',
                    'label_id',
                ],
                50,
                DBBatchInsert::REPLACE_RECORDS,
            );

            $to_parent_type = DB::escape(get_class($to));
            $to_parent_id = DB::escape($to->getId());

            foreach ($label_ids as $label_id) {
                $batch->insertEscapedArray(
                    [
                        $to_parent_type,
                        $to_parent_id,
                        DB::escape($label_id),
                    ],
                );
            }

            $batch->done();
        }

        return $this;
    }

    private function prepareLabelsAttributeValue(array $input_value): array
    {
        // input values are integers
        if (array_filter($input_value, 'is_int') === $input_value) {
            $label_names = count($input_value)
                ? DB::executeFirstColumn('SELECT UPPER(name) FROM labels WHERE id IN (?)', $input_value)
                : [];

            return is_array($label_names) ? $label_names : [];
        // input values are strings
        } else {
            $result = [];

            foreach ($input_value as $k => $v) {
                $label_name = $this->prepareLabelName($v);

                if (!empty($label_name)) {
                    $result[] = $label_name;
                }
            }

            return $result;
        }
    }

    private function prepareLabelName($label_name): string
    {
        return is_string($label_name) ? mb_strtoupper(trim($label_name)) : '';
    }

    private function saveLabelsFromAttribute(array $attribute_value): bool
    {
        $new_label_added = false;

        if (!empty($attribute_value)) {
            $existing_labels = $this->getExistingLabelIdNameMap($attribute_value);

            $labels_to_insert = empty($existing_labels)
                ? []
                : array_values($existing_labels);

            foreach ($attribute_value as $label_name) {
                if (array_key_exists(strtolower_utf($label_name), $existing_labels)) {
                    continue;
                }

                $label = Labels::create(
                    [
                        'type' => $this->getLabelType(),
                        'name' => mb_strtoupper($label_name),
                    ],
                );

                if ($label) {
                    $new_label_added = true;
                    $labels_to_insert[] = $label->getId();
                }
            }

            $this->insertLabels($labels_to_insert);
        }

        return $new_label_added;
    }

    private function getExistingLabelIdNameMap(array $attribute_value): array
    {
        $result = [];

        $rows = DB::execute(
            'SELECT `id`, `name` FROM `labels` WHERE `type` = ? AND `name` IN (?) ORDER BY `id`',
            $this->getLabelType(),
            $attribute_value,
        );

        if ($rows) {
            foreach ($rows as $row) {
                $result[strtolower_utf($row['name'])] = $row['id'];
            }
        }

        return $result;
    }

    private function insertLabels(array $labels_to_insert)
    {
        try {
            DB::beginWork('Begin: set labels @ ' . __CLASS__);

            $batch = new DBBatchInsert(
                'parents_labels',
                [
                    'parent_type',
                    'parent_id',
                    'label_id',
                ],
                50,
                DBBatchInsert::REPLACE_RECORDS,
            );

            $parent_type = DB::escape(get_class($this));
            $parent_id = DB::escape($this->getId());

            foreach ($labels_to_insert as $label_id) {
                $batch->insertEscapedArray(
                    [
                        $parent_type,
                        $parent_id,
                        DB::escape($label_id),
                    ],
                );
            }

            $batch->done();

            DB::commit('Done: set labels @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: set labels @ ' . __CLASS__);

            throw $e;
        }
    }

    abstract protected function registerEventHandler(string $event, callable $handler): void;
    abstract public function getId();
    abstract public function getLabelType(): string;
}
