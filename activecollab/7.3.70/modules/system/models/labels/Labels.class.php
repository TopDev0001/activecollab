<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Wrappers\ConfigOptions\ConfigOptionsInterface;

class Labels extends BaseLabels
{
    const LABELS_NAME_MAX_LENGTH = 30;

    public static function getColorPalette(): array
    {
        return array_keys(Label::COLOR_PALETTE);
    }

    private static function getLighterTextColorFor(string $color): string
    {
        return self::getTextColorFromPalette($color, 'lighter_text', '#ACACAC');
    }

    private static function getDarkerTextColorFor(string $color): string
    {
        return self::getTextColorFromPalette($color, 'darker_text', '#808080');
    }

    private static function getTextColorFromPalette(string $color, string $key, string $default): string
    {
        return array_key_exists($color, Label::COLOR_PALETTE)
            ? Label::COLOR_PALETTE[$color][$key]
            : $default;
    }

    /**
     * Return new collection.
     *
     * @param  User|null       $user
     * @return ModelCollection
     */
    public static function prepareCollection(string $collection_name, $user)
    {
        $collection = parent::prepareCollection($collection_name, $user);

        switch ($collection_name) {
            case 'project_labels':
                $collection->setConditions(['type = ?', ProjectLabel::class]);
                break;
            case 'task_labels':
                $collection->setConditions(['type = ?', TaskLabel::class]);
                break;
            case DataManager::ALL:
                break;
            default:
                throw new InvalidParamError('collection_name', $collection_name);
        }

        return $collection;
    }

    public static function getLabelIdsByProject(Project $project): array
    {
        $label_ids = DB::executeFirstColumn(
            'SELECT DISTINCT pl.label_id FROM parents_labels AS pl LEFT JOIN tasks AS t ON pl.parent_type = ? AND pl.parent_id = t.id WHERE t.project_id = ?',
            Task::class,
            $project->getId()
        );

        if (empty($label_ids)) {
            $label_ids = [];
        }

        return $label_ids;
    }

    public static function getLabelsDetailsByType(string $label_type): array
    {
        $results = [];

        $labels = DB::execute('SELECT * FROM `labels` WHERE `type` = ?', $label_type);

        if (!empty($labels)) {
            foreach ($labels as $label) {
                $label_color = strtoupper($label['color']);
                $color = array_key_exists($label_color, Label::COLOR_PALETTE)
                    ? $label_color
                    : Label::LABEL_DEFAULT_COLOR;

                $results[] = [
                    'id' => $label['id'],
                    'class' => $label_type,
                    'name' => $label['name'],
                    'color' => $color,
                    'darker_text_color' => self::getDarkerTextColorFor((string) $color),
                    'lighter_text_color' => self::getLighterTextColorFor((string) $color),
                    'is_default' => $label['is_default'],
                    'is_global' => $label['is_global'],
                    'position' => $label['position'],
                    'url_path' => sprintf('/labels/%d', $label['id']),
                ];
            }
        }

        return $results;
    }

    /**
     * Reorder labels.
     *
     * @param Label[]|int[] $labels
     */
    public static function reorder($labels)
    {
        if (!empty($labels)) {
            DB::transact(
                function () use ($labels) {
                    $counter = 1;
                    $timestamp = DateTimeValue::now();

                    foreach ($labels as $label) {
                        DB::execute(
                            'UPDATE `labels` SET `position` = ?, `updated_on` = ? WHERE `id` = ?',
                            $counter++,
                            $timestamp,
                            $label instanceof Label ? $label->getId() : $label
                        );
                    }
                },
                'Reordering labels'
            );
        }

        Labels::clearCache();
    }

    public static function canAdd(User $user): bool
    {
        return $user->canManageTasks();
    }

    public static function canReorder(IUser $user): bool
    {
        return $user->isOwner();
    }

    public static function getIdNameMap(string $type): array
    {
        $result = AngieApplication::cache()->get(
            [
                'models',
                'labels',
                'id_name_map_for_' . $type,
            ],

            function () use ($type) {
                $result = [];

                if ($labels = Labels::findByType($type)) {
                    foreach ($labels as $label) {
                        $result[$label->getId()] = [
                            $label->getName(),
                            'color' => $label->getColor(),
                        ];
                    }
                }

                return $result;
            }
        );

        if (empty($result) || !is_array($result)) {
            $result = [];
        }

        return $result;
    }

    private static array $details_by_parent = [];

    public static function preloadDetailsByParents(string $parent_type, array $parent_ids): void
    {
        self::$details_by_parent[$parent_type] = [];

        $rows = DB::execute(
            'SELECT pl.parent_id, l.id, l.name, l.color
                FROM labels AS l LEFT JOIN parents_labels AS pl ON l.id = pl.label_id
                WHERE pl.parent_type = ? AND pl.parent_id IN (?) ORDER BY `name`',
            $parent_type,
            $parent_ids
        );

        if (!empty($rows)) {
            foreach ($rows as $row) {
                if (empty(self::$details_by_parent[$parent_type][$row['parent_id']])) {
                    self::$details_by_parent[$parent_type][$row['parent_id']] = [];
                }

                if ($row['color'] && !empty(Label::COLOR_PALETTE[$row['color']])) {
                    $color = $row['color'];
                } else {
                    $color = Label::LABEL_DEFAULT_COLOR;
                }

                self::$details_by_parent[$parent_type][$row['parent_id']][] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'color' => $color,
                    'lighter_text_color' => Label::COLOR_PALETTE[$color]['lighter_text'],
                    'darker_text_color' => Label::COLOR_PALETTE[$color]['darker_text'],
                ];
            }
        }
        if ($zeros = array_diff($parent_ids, array_keys(self::$details_by_parent[$parent_type]))) {
            foreach ($zeros as $parent_with_no_labels) {
                self::$details_by_parent[$parent_type][$parent_with_no_labels] = [];
            }
        }
    }

    public static function resetState()
    {
        self::$details_by_parent = [];
    }

    private static array $ids_by_parent = [];

    public static function preloadIdsByParents($parent_type, array $parent_ids)
    {
        self::$ids_by_parent[$parent_type] = [];

        $rows = DB::execute(
            'SELECT pl.parent_id, pl.label_id 
                 FROM parents_labels pl
                 LEFT JOIN labels l
                 ON l.id = pl.label_id
                 WHERE parent_type = ? AND parent_id IN (?) ORDER BY l.name ASC',
            $parent_type,
            $parent_ids
        );

        if (!empty($rows)) {
            foreach ($rows as $row) {
                if (empty(self::$ids_by_parent[$parent_type][$row['parent_id']])) {
                    self::$ids_by_parent[$parent_type][$row['parent_id']] = [];
                }

                self::$ids_by_parent[$parent_type][$row['parent_id']][] = $row['label_id'];
            }
        }

        if ($zeros = array_diff($parent_ids, array_keys(self::$ids_by_parent[$parent_type]))) {
            foreach ($zeros as $parent_with_no_labels) {
                self::$ids_by_parent[$parent_type][$parent_with_no_labels] = [];
            }
        }
    }

    public static function getIdsByParentTypeAndParentId(string $parent_type, int $parent_id)
    {
        if (isset(self::$ids_by_parent[$parent_type][$parent_id])) {
            return self::$ids_by_parent[$parent_type][$parent_id];
        } else {
            $label_ids = DB::executeFirstColumn(
                'SELECT label_id FROM parents_labels WHERE parent_type = ? AND parent_id = ?',
                $parent_type,
                $parent_id
            );

            return !empty($label_ids) ? $label_ids : [];
        }
    }

    /**
     * @param  DataObject|ILabels $parent
     * @return array
     */
    public static function getDetailsByParent(ILabels $parent)
    {
        $parent_type = get_class($parent);
        $parent_id = $parent->getId();

        if (isset(self::$details_by_parent[$parent_type][$parent_id])) {
            return self::$details_by_parent[$parent_type][$parent_id];
        } else {
            $result = [];

            $rows = DB::execute(
                'SELECT l.`id`, l.`name`, l.`color`, l.`is_default`, l.`is_global`, l.`position`
                    FROM `labels` AS l LEFT JOIN `parents_labels` AS pl ON l.`id` = pl.`label_id`
                    WHERE pl.`parent_type` = ? AND pl.`parent_id` = ? ORDER BY `name`',
                $parent_type,
                $parent_id
            );

            if (!empty($rows)) {
                foreach ($rows as $row) {
                    if ($row['color'] && !empty(Label::COLOR_PALETTE[strtoupper($row['color'])])) {
                        $color = strtoupper($row['color']);
                    } else {
                        $color = Label::LABEL_DEFAULT_COLOR;
                    }

                    $result[] = [
                        'id' => $row['id'],
                        'name' => $row['name'],
                        'color' => $color,
                        'darker_text_color' => Label::COLOR_PALETTE[$color]['darker_text'],
                        'lighter_text_color' => Label::COLOR_PALETTE[$color]['lighter_text'],
                        'is_default' => $row['is_default'],
                        'is_global' => $row['is_global'],
                        'position' => $row['position'],
                        'url_path' => '/labels/' . $row['id'],
                    ];
                }
            }

            return $result;
        }
    }

    public static function getLabelName(
        int $label_id,
        string $default = null
    ): ?string
    {
        $names = AngieApplication::cache()->get(
            [
                'models',
                'labels',
                'id_name_map',
            ],
            function () {
                $result = [];

                $rows = DB::execute('SELECT `id`, UPPER(`name`) AS "name" FROM `labels`');

                if (!empty($rows)) {
                    foreach ($rows as $row) {
                        $result[$row['id']] = $row['name'];
                    }
                }

                return $result;
            }
        );

        return $names[$label_id] ?? $default;
    }

    public static function getNamesByIds(array $label_ids): array
    {
        if (!empty($label_ids)) {
            $rows = DB::execute('SELECT `id`, `name` FROM `labels` WHERE `id` IN (?)', $label_ids);

            if (!empty($rows)) {
                $result = [];

                foreach ($rows as $row) {
                    $result[$row['id']] = $row['name'];
                }

                return $result;
            }
        }

        return [];
    }

    /**
     * Return label ID-s by list of label names.
     *
     * @param  array  $names
     * @param  string $type
     * @return array
     */
    public static function getIdsByNames($names, $type)
    {
        if ($names && is_foreachable($names)) {
            return DB::executeFirstColumn('SELECT id FROM labels WHERE name IN (?) AND type = ? ORDER BY position', $names, $type);
        }

        return null;
    }

    /**
     * Return labels by type name.
     *
     * @param  string                $type
     * @return Label[]|DBResult|null
     */
    public static function findByType($type)
    {
        return Labels::find(['conditions' => ['type = ?', $type]]);
    }

    /**
     * Return default label by given type.
     *
     * @param  string           $type
     * @return Label|DataObject
     */
    public static function findDefault($type)
    {
        return DataObjectPool::get('Label', static::findDefaultId($type));
    }

    /**
     * Return ID of the default label.
     *
     * @param  string   $type
     * @return int|null
     */
    public static function findDefaultId($type)
    {
        return AngieApplication::cache()->get(['models', 'labels', "default_{$type}_id"], function () use ($type) {
            return DB::executeFirstCell('SELECT id FROM labels WHERE type = ? AND is_default = ? LIMIT 0, 1', $type, true);
        });
    }

    /**
     * Set $label as default.
     *
     * @throws Exception
     */
    public static function setDefault(Label $label)
    {
        if ($label->getIsDefault()) {
            return;
        }

        try {
            DB::beginWork('Setting default label @ ' . __CLASS__);

            $label->setIsDefault(true);
            $label->save();

            DB::execute('UPDATE labels SET is_default = ? WHERE id != ? AND type = ?', false, $label->getId(), get_class($label));

            DB::commit('Default label set @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Failed to set default label @ ' . __CLASS__);
            throw $e;
        }

        Labels::clearCache();
    }

    /**
     * Unset default label for given type.
     *
     * @throws Exception
     */
    public static function unsetDefault(Label $label)
    {
        if (!$label->getIsDefault()) {
            return;
        }

        try {
            DB::beginWork('Unsetting default label @ ' . __CLASS__);

            $label->setIsDefault(false);
            $label->save();

            DB::execute('UPDATE labels SET is_default = ? WHERE id != ? AND type = ?', false, $label->getId(), get_class($label));

            DB::commit('Default label unset @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Failed to unset default label @ ' . __CLASS__);
            throw $e;
        }
    }

    // ---------------------------------------------------
    //  Subquery
    // ---------------------------------------------------

    /**
     * Return ID-s of objects that have one or all of the provided labels.
     *
     * @param  string $parent_type
     * @param  string $label_type
     * @param  array  $label_names
     * @param  bool   $must_have_all_labels
     * @return int[]
     */
    public static function getParentIdsByLabels($parent_type, $label_type, $label_names, $must_have_all_labels = true)
    {
        if ($label_names && is_foreachable($label_names)) {
            $result = DB::executeFirstColumn(Labels::getParentIdsByLabelsSqlQuery($parent_type, $label_type, $label_names, $must_have_all_labels));
        }

        return empty($result) ? [] : $result;
    }

    /**
     * Prepare SQL that will query for one or more of the labels.
     *
     * @param  string $parent_type
     * @param  string $label_type
     * @param  array  $label_names
     * @param  bool   $must_have_all_labels
     * @return string
     */
    public static function getParentIdsByLabelsSqlQuery($parent_type, $label_type, $label_names, $must_have_all_labels = true)
    {
        if ($must_have_all_labels) {
            return DB::prepare('SELECT DISTINCT pl.parent_id AS id FROM parents_labels AS pl LEFT JOIN labels AS l ON pl.label_id = l.id WHERE l.type = ? AND l.name IN (?) AND pl.parent_type = ? GROUP BY pl.parent_id HAVING COUNT(l.name) = ?', $label_type, $label_names, $parent_type, count($label_names));
        } else {
            return DB::prepare('SELECT DISTINCT pl.parent_id AS id FROM parents_labels AS pl LEFT JOIN labels AS l ON pl.label_id = l.id WHERE l.type = ? AND l.name IN (?) AND pl.parent_type = ? ORDER BY pl.parent_id', $label_type, $label_names, $parent_type);
        }
    }

    /**
     * Return ID-s of objects that don't have a label.
     *
     * @param  string $parent_table
     * @param  string $parent_type
     * @return int[]
     */
    public static function getParentIdsWithNoLabels($parent_table, $parent_type)
    {
        $result = DB::executeFirstColumn(Labels::getParentIdsWithNoLabelsSqlQuery($parent_table, $parent_type));

        return empty($result) ? [] : $result;
    }

    /**
     * Prepare query that will return all unlabeled entries from a given table.
     *
     * @param  string $parent_table
     * @param  string $parent_type
     * @return string
     */
    public static function getParentIdsWithNoLabelsSqlQuery($parent_table, $parent_type)
    {
        return DB::prepare(
            "SELECT id FROM $parent_table WHERE NOT EXISTS (SELECT * FROM parents_labels AS pl WHERE pl.parent_type = ? AND $parent_table.id = pl.parent_id)",
            $parent_type
        );
    }

    private static function getExistingLabelByAttributes(array $attributes): ?Label
    {
        $existing_label_id = DB::executeFirstCell(
            'SELECT `id` FROM `labels` WHERE `name` = UPPER(?) AND `type` = ?',
            array_var($attributes, 'name'),
            array_var($attributes, 'type')
        );

        if ($existing_label_id) {
            return Labels::findById($existing_label_id);
        }

        return null;
    }

    public static function create(
        array $attributes,
        bool $save = true,
        bool $announce = true
    ): Label
    {
        $existing_label = self::getExistingLabelByAttributes($attributes);

        if ($existing_label) {
            return parent::update(
                $existing_label,
                self::cleanUpLabelAttributesForOverride($attributes),
                $save
            );
        }

        return parent::create($attributes, $save, $announce);
    }

    public static function &update(
        DataObject &$instance,
        array $attributes,
        bool $save = true
    ): Label
    {
        $renamed_to_existing_label = self::isRenamedToExistingLabel($instance, $attributes);

        if ($renamed_to_existing_label) {
            try {
                DB::beginWork('Update existing label @ ' . __CLASS__);

                DB::execute(
                    'UPDATE `parents_labels` SET `label_id` = ? WHERE `label_id` = ?',
                    $renamed_to_existing_label->getId(),
                    $instance->getId()
                );

                parent::update(
                    $renamed_to_existing_label,
                    self::cleanUpLabelAttributesForOverride($attributes),
                    $save
                );

                AngieApplication::cache()->removeByObject($instance);
                parent::scrap($instance, true);

                DB::commit('Existing label updated @ ' . __CLASS__);

                return $renamed_to_existing_label;
            } catch (Exception $e) {
                DB::rollback('Failed to update existing label @ ' . __CLASS__);
                throw $e;
            }
        }

        return parent::update($instance, $attributes, $save);
    }

    private static function isRenamedToExistingLabel(DataObject $instance, array $attributes): ?Label
    {
        if (self::isRenamed($instance, $attributes)) {
            $existing_label = self::getExistingLabelByAttributes($attributes);

            if ($existing_label) {
                return $existing_label;
            }
        }

        return null;
    }

    private static function isRenamed(DataObject $instance, array $attributes): bool
    {
        return !empty($attributes['name'])
            && is_string($attributes['name'])
            && strtolower_utf($attributes['name']) != strtolower_utf($instance->getName());
    }

    private static function cleanUpLabelAttributesForOverride(array $attributes): array
    {
        if (self::shouldAutoPromoteTaskLabels()) {
            $attributes = array_merge(
                $attributes,
                [
                    'is_global' => true,
                ]
            );
        }

        if (!empty($attributes['color'])
            && ($attributes['color'] === Label::LABEL_DEFAULT_COLOR || $attributes['color'] === Label::LABEL_DEFAULT_COLOR_SHORT)
        ) {
            unset($attributes['color']);
        }

        return $attributes;
    }

    private static function shouldAutoPromoteTaskLabels(): bool
    {
        return (bool) AngieApplication::getContainer()
            ->get(ConfigOptionsInterface::class)
                ->getValue('auto_promote_task_labels');
    }
}
