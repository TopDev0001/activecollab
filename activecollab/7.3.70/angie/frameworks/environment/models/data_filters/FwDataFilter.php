<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Globalization;
use Angie\Inflector;

abstract class FwDataFilter extends BaseDataFilter implements ICreatedBy
{
    const EXPORT_ERROR_ALREADY_STARTED = 0;
    const EXPORT_ERROR_CANT_OPEN_HANDLE = 1;
    const EXPORT_ERROR_HANDLE_NOT_OPEN = 2;

    abstract public function getExportColumns(): array;
    abstract public function exportWriteLines(User $user, array $result): void;

    public function export(User $user, array $additional = null): ?string
    {
        $this->ungroup();

        $result = $this->run($user, $additional);

        if (!empty($result)) {
            $this->beginExport($this->getExportColumns());
            $this->exportWriteLines($user, $result);

            return $this->completeExport();
        }

        return null;
    }

    private function getExportFileName(): string
    {
        return Inflector::underscore(get_class($this));
    }

    public function jsonSerialize(): array
    {
        $result = parent::jsonSerialize();

        if (!empty($this->canBeGroupedBy())) {
            $result['group_by'] = $this->getGroupBy();
        }

        return $result;
    }

    /**
     * Set non-field value during DataManager::create() and DataManager::update() calls.
     *
     * @param string $attribute
     * @param mixed  $value
     */
    public function setAttribute($attribute, $value)
    {
        if ($attribute === 'group_by') {
            $this->setGroupBy($value);
        } else {
            $method = 'set' . Inflector::camelize($attribute);

            if (method_exists($this, $method)) {
                $this->$method($value);
            } else {
                parent::setAttribute($attribute, $value);
            }
        }
    }

    public function getRoutingContext(): string
    {
        return 'data_filter';
    }

    public function getRoutingContextParams(): array
    {
        return [
            'data_filter_id' => $this->getId(),
        ];
    }

    /**
     * Return ID from filter value.
     *
     * @param  string $value
     * @return int
     */
    protected function getIdFromFilterValue($value)
    {
        $bits = explode('_', $value);
        $last_bit = array_pop($bits);

        if (is_numeric($last_bit)) {
            return (int) $last_bit;
        } else {
            throw new InvalidParamError('value', $value, 'Last value bit is not a valid ID');
        }
    }

    /**
     * Return ID-s from filter value.
     *
     * ID-s are expected to be the parameter in the filter value. Like:
     *
     * company_1
     *
     * or:
     *
     * selected_1,2,3,4
     *
     * @param  string $value
     * @return int[]
     */
    protected function getIdsFromFilterValue($value)
    {
        $bits = explode('_', $value);
        $ids = array_pop($bits);

        if (strpos($ids, ',') === false) {
            if (is_numeric($ids)) {
                return [(int) $ids];
            } else {
                throw new InvalidParamError('value', $value, 'Last bit is not a valid ID or list of IDs');
            }
        } else {
            $result = [];

            foreach (explode(',', $ids) as $id) {
                $result[] = (int) trim($id);
            }

            return array_unique($result);
        }
    }

    /**
     * Return names from filter value.
     *
     * Names are expected to be the parameter in the filter value. Like:
     *
     * selected_NEW
     *
     * or:
     *
     * not_selected_NEW,UPDATED,CANCELED
     *
     * @param  string   $value
     * @param  string   $filter_name
     * @return string[]
     */
    protected function getNamesFromFilterValue($value, $filter_name)
    {
        $bits = explode($filter_name . '_', $value);
        $names = array_pop($bits);

        if (strpos($names, ',') === false) {
            return [trim($names)];
        } else {
            $result = [];

            foreach (explode(',', $names) as $name) {
                $result[] = trim($name);
            }

            return array_unique($result);
        }
    }

    /**
     * Return date from filter value.
     *
     * Example:
     *
     * selected_date_2014-10-17
     *
     * @param  string $value
     * @return string
     */
    protected function getDateFromFilterValue($value)
    {
        $bits = explode('_', $value);

        return array_pop($bits);
    }

    /**
     * Return date from filter value.
     *
     * Example:
     *
     * selected_date_range_2014-10-17:2014-10-27
     *
     * @param  string   $value
     * @return string[]
     */
    protected function getDateRangeFromFilterValue($value)
    {
        $bits = explode('_', $value);
        $range = array_pop($bits);

        if (strpos($range, ':') !== false) {
            return explode(':', $range);
        } else {
            throw new InvalidParamError(
                'value',
                $value,
                'Range in YYYY-MM-DD:YYYY-MM-DD expected'
            );
        }
    }

    /**
     * Cached map of user display names indexed by user ID.
     *
     * @var array
     */
    private $users_map = false;

    /**
     * Get display name based on given parameters.
     *
     * @param  int    $user_id
     * @param  mixed  $user_display_name_elements
     * @return string
     */
    protected function getUserDisplayName($user_id, $user_display_name_elements = null)
    {
        if ($user_id) {
            if ($this->users_map === false) {
                $this->users_map = Users::getIdNameMap(null, true);
            }

            if ($this->users_map && isset($this->users_map[$user_id])) {
                return $this->users_map[$user_id];
            } else {
                if ($user_display_name_elements) {
                    return Users::getUserDisplayName($user_display_name_elements);
                } else {
                    return lang('Unknown');
                }
            }
        } else {
            return null;
        }
    }

    // ---------------------------------------------------
    //  User Filter Utility Methods
    // ---------------------------------------------------

    const USER_FILTER_ANYBODY = 'anybody';
    const USER_FILTER_USER_SET = 'set';
    const USER_FILTER_USER_NOT_SET = 'not_set';
    const USER_FILTER_ANONYMOUS = 'anonymous';
    const USER_FILTER_LOGGED_USER = 'logged_user';
    const USER_FILTER_SELECTED = 'selected';

    /**
     * Returns true if $value is a valid and supported user filter.
     */
    protected function isValidUserFilter(string $value): bool
    {
        return in_array(
            $value,
            [
                self::USER_FILTER_ANYBODY,
                self::USER_FILTER_USER_SET,
                self::USER_FILTER_USER_NOT_SET,
                self::USER_FILTER_ANONYMOUS,
                self::USER_FILTER_LOGGED_USER,
                self::USER_FILTER_SELECTED,
            ]
        );
    }

    /**
     * Return filter by user filer name.
     *
     * @param  string $user_filter_name
     * @return string
     */
    protected function getFilterByUserFilterName($user_filter_name)
    {
        return "{$user_filter_name}_by_filter";
    }

    /**
     * Return field name based on user filter, if $filter_name is empty.
     *
     * @param  string $field_name
     * @param  string $user_filter_name
     * @return string
     */
    protected function getUserFilterFieldName($field_name, $user_filter_name)
    {
        return $field_name ? $field_name : "{$user_filter_name}_by_id";
    }

    /**
     * Cached array of user filter getter.
     *
     * @var array
     */
    private $user_filter_getters = [];

    /**
     * Return user filter getter method names.
     *
     * @param  string $user_filter_name
     * @return array
     */
    protected function getUserFilterGetters($user_filter_name)
    {
        if (!array_key_exists($user_filter_name, $this->user_filter_getters)) {
            $this->user_filter_getters[$user_filter_name] = $this->prepareUserFilterGetterNames($user_filter_name);
        }

        return $this->user_filter_getters[$user_filter_name];
    }

    /**
     * Cached array of user filter setters.
     *
     * @var array
     */
    private $user_filter_setters = [];

    /**
     * Return user filter setter method names.
     *
     * @param  string $user_filter_name
     * @return array
     */
    protected function getUserFilterSetters($user_filter_name)
    {
        if (!array_key_exists($user_filter_name, $this->user_filter_setters)) {
            $this->user_filter_setters[$user_filter_name] = $this->prepareUserFilterSetterNames($user_filter_name);
        }

        return $this->user_filter_setters[$user_filter_name];
    }

    protected function prepareUserFilterGetterNames(string $user_filter_name): array
    {
        $user_filter_camelized = Inflector::camelize($user_filter_name);

        return [
            "get{$user_filter_camelized}ByFilter",
            "get{$user_filter_camelized}ByUsers",
        ];
    }

    protected function prepareUserFilterSetterNames(string $user_filter_name): array
    {
        $user_filter_camelized = Inflector::camelize($user_filter_name);
        $user_filter_prefix = lcfirst($user_filter_camelized);

        return [
            "set{$user_filter_camelized}ByFilter",
            "{$user_filter_prefix}ByUsers",
        ];
    }

    /**
     * Set user filter attributes.
     *
     * @param string $user_filter_name
     * @param string $value
     */
    protected function setUserFilterAttribute($user_filter_name, $value)
    {
        if (str_starts_with((string) $value, self::USER_FILTER_SELECTED)) {
            call_user_func(
                [
                    $this,
                    $this->getUserFilterSetters($user_filter_name)[1],
                ],
                $this->getIdsFromFilterValue($value)
            );
        } else {
            call_user_func(
                [
                    $this,
                    $this->getUserFilterSetters($user_filter_name)[0],
                ],
                $value
            );
        }
    }

    /**
     * Describe user filter.
     *
     * @param string $user_filter_name
     * @param array  $result
     */
    protected function describeUserFilter($user_filter_name, & $result)
    {
        [$filter_getter, $selected_users_getter] = $this->getUserFilterGetters($user_filter_name);

        $user_filter = $this->getFilterByUserFilterName($user_filter_name);

        $result[$user_filter] = $this->$filter_getter();

        if ($result[$user_filter] == self::USER_FILTER_SELECTED) {
            $result["{$user_filter_name}_by_user_ids"] = $this->$selected_users_getter();
        }
    }

    /**
     * Prepare conditions for a particular user filter.
     */
    protected function prepareUserFilterConditions(
        User $user,
        string $user_filter_name,
        string $table_name,
        array & $conditions,
        string $field_name = null,
        int $min_state = STATE_VISIBLE
    )
    {
        [$filter_getter, $selected_users_getter] = $this->getUserFilterGetters($user_filter_name);

        $user_filter = $this->getFilterByUserFilterName($user_filter_name);

        if ($this->isValidUserFilter((string) $this->$filter_getter())) {
            $field_name = $this->getUserFilterFieldName($field_name, $user_filter_name);

            $full_field_name = $table_name ? "`$table_name`.`$field_name`" : "`$field_name`";

            switch ($this->$filter_getter()) {
                case self::USER_FILTER_ANYBODY:
                    break;

                // User set
                case self::USER_FILTER_USER_SET:
                    $conditions[] = DB::prepare("($full_field_name != '0')");

                    break;

                // User not set
                case self::USER_FILTER_USER_NOT_SET:
                    $conditions[] = DB::prepare("($full_field_name = '0')");

                    break;

                // Logged user
                case self::USER_FILTER_LOGGED_USER:
                    $conditions[] = DB::prepare("($full_field_name = ?)", $user->getId());

                    break;

                // Selected users
                case self::USER_FILTER_SELECTED:
                    $user_ids = $this->$selected_users_getter();

                    if ($user_ids) {
                        $visible_user_ids = $user->getVisibleUserIds($min_state);

                        if ($visible_user_ids) {
                            foreach ($user_ids as $k => $v) {
                                // this probably should be refactored, when user permanently deleted, its time records are set to 0
                                if ($v !== 0 && !in_array($v, $visible_user_ids)) {
                                    unset($user_ids[$k]);
                                }
                            }

                            if (count($user_ids)) {
                                $conditions[] = DB::prepare("($full_field_name IN (?))", $user_ids);
                            } else {
                                throw new DataFilterConditionsError(
                                    $user_filter,
                                    self::USER_FILTER_SELECTED,
                                    $user_ids,
                                    'Non of the selected users is visible'
                                );
                            }
                        } else {
                            throw new DataFilterConditionsError(
                                $user_filter,
                                self::USER_FILTER_SELECTED,
                                $user_ids,
                                "User can't see anyone else"
                            );
                        }
                    } else {
                        throw new DataFilterConditionsError(
                            $user_filter,
                            self::USER_FILTER_SELECTED,
                            $user_ids,
                            'No users selected'
                        );
                    }

                    break;
            }
        } else {
            throw new DataFilterConditionsError(
                $user_filter,
                $this->$filter_getter(),
                'mixed',
                'Unknown user filter'
            );
        }
    }

    /**
     * Return projects grouped by leader.
     *
     * @param array  $records
     * @param string $user_field
     * @param string $user_not_set_label
     * @param string $collection_name
     */
    protected function groupByUser(
        $records,
        array & $result,
        $user_field = 'user_id',
        $user_not_set_label = null,
        $collection_name = 'records'
    )
    {
        if (empty($user_not_set_label)) {
            $user_not_set_label = lang('Unknown');
        }

        $user_ids = [];

        foreach ($records as $record) {
            if ($record[$user_field] && !in_array($record[$user_field], $user_ids)) {
                $user_ids[] = $record[$user_field];
            }
        }

        if (count($user_ids)) {
            $user_id_name_map = Users::getIdNameMap($user_ids);

            foreach ($user_id_name_map as $user_id => $user_name) {
                $result["user-$user_id"] = ['label' => $user_name, $collection_name => []];
            }
        }

        $result['user-not-set'] = ['label' => $user_not_set_label, $collection_name => []];

        foreach ($records as $record) {
            $user_id = $record[$user_field];

            if (isset($result["user-$user_id"])) {
                $result["user-$user_id"][$collection_name][$record['id']] = $record;
            } else {
                $result['user-not-set'][$collection_name][$record['id']] = $record;
            }
        }

        if (empty($result['user-not-set'][$collection_name])) {
            unset($result['user-not-set']);
        }
    }

    // ---------------------------------------------------
    //  Date Filter Utility Methods
    // ---------------------------------------------------

    // Date filter
    const DATE_FILTER_ANY = 'any';
    const DATE_FILTER_IS_SET = 'is_set';
    const DATE_FILTER_IS_NOT_SET = 'is_not_set';
    const DATE_FILTER_LATE = 'late';
    const DATE_FILTER_LATE_OR_TODAY = 'late_or_today';
    const DATE_FILTER_STARTED = 'started';
    const DATE_FILTER_YESTERDAY = 'yesterday';
    const DATE_FILTER_TODAY = 'today';
    const DATE_FILTER_TOMORROW = 'tomorrow';
    const DATE_FILTER_LAST_WEEK = 'last_week';
    const DATE_FILTER_THIS_WEEK = 'this_week';
    const DATE_FILTER_NEXT_WEEK = 'next_week';
    const DATE_FILTER_LAST_MONTH = 'last_month';
    const DATE_FILTER_THIS_MONTH = 'this_month';
    const DATE_FILTER_NEXT_MONTH = 'next_month';
    const DATE_FILTER_LAST_YEAR = 'last_year';
    const DATE_FILTER_THIS_YEAR = 'this_year';
    const DATE_FILTER_SELECTED_YEAR = 'selected_year';
    const DATE_FILTER_AGE_IS = 'age_is';
    const DATE_FILTER_AGE_IS_MORE_THAN = 'age_is_more_than';
    const DATE_FILTER_AGE_IS_LESS_THAN = 'age_is_less_than';
    const DATE_FILTER_BEFORE_SELECTED_DATE = 'before_selected_date';
    const DATE_FILTER_BEFORE_AND_ON_SELECTED_DATE = 'before_selected_date_inclusive';
    const DATE_FILTER_SELECTED_DATE = 'selected_date';
    const DATE_FILTER_AFTER_SELECTED_DATE = 'after_selected_date';
    const DATE_FILTER_AFTER_AND_ON_SELECTED_DATE = 'after_selected_date_inclusive';
    const DATE_FILTER_SELECTED_RANGE = 'selected_range';

    protected function getDateFilterGetters(string $date_filter_name): array
    {
        $upper_case_date_filter_name = Inflector::camelize($date_filter_name);

        return [
            "get{$upper_case_date_filter_name}OnFilter",
            "get{$upper_case_date_filter_name}InYear",
            "get{$upper_case_date_filter_name}Age",
            "get{$upper_case_date_filter_name}OnDate",
            "get{$upper_case_date_filter_name}InRange",
        ];
    }

    protected function getDateFilterSetters(string $date_filter_name): array
    {
        return [
            'set' . Inflector::camelize($date_filter_name) . 'OnFilter',
            "{$date_filter_name}InYear",
            "{$date_filter_name}Age",
            "{$date_filter_name}OnDate",
            "{$date_filter_name}BeforeDate",
            "{$date_filter_name}AfterDate",
            "{$date_filter_name}InRange",
        ];
    }

    /**
     * Set date filter settings from attributes.
     *
     * @param string $date_filter_name
     * @param string $value
     */
    protected function setDateFilterAttribute($date_filter_name, $value)
    {
        [
            $filter_setter,
            $in_year_setter,
            $age_setter,
            $on_date_setter,
            $before_date_setter,
            $after_date_setter,
            $in_range_setter,
        ] = $this->getDateFilterSetters($date_filter_name);

        if (str_starts_with((string) $value, self::DATE_FILTER_AGE_IS_MORE_THAN)) {
            $this->$age_setter($this->getIdFromFilterValue($value), self::DATE_FILTER_AGE_IS_MORE_THAN);
        } elseif (str_starts_with((string) $value, self::DATE_FILTER_AGE_IS_LESS_THAN)) {
            $this->$age_setter((string) $this->getIdFromFilterValue($value), self::DATE_FILTER_AGE_IS_LESS_THAN);
        } elseif (str_starts_with((string) $value, self::DATE_FILTER_AGE_IS)) {
            $this->$age_setter($this->getIdFromFilterValue($value), self::DATE_FILTER_AGE_IS);
        } elseif (str_starts_with((string) $value, self::DATE_FILTER_SELECTED_YEAR)) {
            $this->$in_year_setter($this->getIdFromFilterValue($value), self::DATE_FILTER_AGE_IS_MORE_THAN);
        } elseif (str_starts_with((string) $value, self::DATE_FILTER_SELECTED_DATE)) {
            $this->$on_date_setter($this->getDateFromFilterValue($value));
        } elseif (str_starts_with((string) $value, self::DATE_FILTER_BEFORE_SELECTED_DATE)) {
            $this->$before_date_setter($this->getDateFromFilterValue($value), $this->isFilterInclusive($value));
        } elseif (str_starts_with((string) $value, self::DATE_FILTER_AFTER_SELECTED_DATE)) {
            $this->$after_date_setter($this->getDateFromFilterValue($value), $this->isFilterInclusive($value));
        } elseif (str_starts_with((string) $value, self::DATE_FILTER_SELECTED_RANGE)) {
            [$from, $to] = $this->getDateRangeFromFilterValue($value);

            $this->$in_range_setter($from, $to);
        } else {
            $this->$filter_setter($value);
        }
    }

    /**
     * Describe date filter.
     *
     * @param string $date_filter_name
     * @param array  $result
     */
    protected function describeDateFilter($date_filter_name, & $result)
    {
        [$filter_getter, $in_year_getter, $age_getter, $on_date_getter, $in_range_getter] = $this->getDateFilterGetters($date_filter_name);

        $date_filter = "{$date_filter_name}_on_filter";

        $result[$date_filter] = $this->$filter_getter();
        switch ($this->$filter_getter()) {
            case self::DATE_FILTER_AGE_IS:
            case self::DATE_FILTER_AGE_IS_LESS_THAN:
            case self::DATE_FILTER_AGE_IS_MORE_THAN:
                $result["{$date_filter_name}_age"] = $this->$age_getter();

                break;
            case self::DATE_FILTER_SELECTED_DATE:
            case self::DATE_FILTER_BEFORE_SELECTED_DATE:
            case self::DATE_FILTER_BEFORE_AND_ON_SELECTED_DATE:
            case self::DATE_FILTER_AFTER_SELECTED_DATE:
            case self::DATE_FILTER_AFTER_AND_ON_SELECTED_DATE:
                $result["{$date_filter_name}_on"] = $this->$on_date_getter();

                break;

            case self::DATE_FILTER_SELECTED_RANGE:
                [$from, $to] = $this->$in_range_getter();

                $result["{$date_filter_name}_from"] = $from;
                $result["{$date_filter_name}_to"] = $to;

                break;
        }
    }

    /**
     * Prepare conditions for a particular date filter.
     *
     * @param string $date_filter_name
     * @param string $table_name
     * @param array  $conditions
     * @param string $field_name
     */
    protected function prepareDateFilterConditions(
        User $user,
        $date_filter_name,
        $table_name,
        & $conditions,
        $field_name = null
    )
    {
        [
            $filter_getter,
            $in_year_getter,
            $age_getter,
            $on_date_getter,
            $in_range_getter
        ] = $this->getDateFilterGetters($date_filter_name);

        $date_filter = "{$date_filter_name}_on_filter";

        if (empty($field_name)) {
            $field_name = "{$date_filter_name}_on";
        }

        $full_filed_name = $table_name ? "`$table_name`.`$field_name`" : "`$field_name`";

        $user_gmt_offset = $this->getTodayReference() ? Globalization::getUserGmtOffsetOnDate($user, $this->getTodayReference()) : Globalization::getUserGmtOffset($user);

        $today = DateTimeValue::now()->advance($user_gmt_offset, false)->beginningOfDay();

        switch ($this->$filter_getter()) {
            case self::DATE_FILTER_ANY:
                break;

            // List items where we have the value set
            case self::DATE_FILTER_IS_SET:
                $conditions[] = "($full_filed_name IS NOT NULL)";

                break;

            // List items where we don't have date value set
            case self::DATE_FILTER_IS_NOT_SET:
                if ($table_name == 'tasks' && $field_name == 'start_on') {
                    throw new LogicException('This filter can not be set on tasks table for start_on field');
                } else {
                    $conditions[] = "($full_filed_name IS NULL)";
                }

                break;

            // List late items
            case self::DATE_FILTER_LATE:
                $conditions[] = DB::prepare("($full_filed_name < ?)", $today);

                break;

            // List late or today items
            case self::DATE_FILTER_LATE_OR_TODAY:
            case self::DATE_FILTER_STARTED:
                $conditions[] = DB::prepare("($full_filed_name <= ?)", $today);

                break;

            // List items that match yesterday
            case self::DATE_FILTER_YESTERDAY:
                if ($user_gmt_offset != 0 && $this->calculateTimezoneWhenFilteringByDate($field_name)) {
                    $yesterday = DateValue::makeFromTimestamp(strtotime('-1 day', $today->getTimestamp()))->format('Y-m-d');
                    $yesterday = DateValue::makeFromString($yesterday);

                    [$from, $to] = $this->getGmtRangeForDate($yesterday, $user_gmt_offset);

                    $conditions[] = DB::prepare("($full_filed_name BETWEEN ? AND ?)", $from, $to);
                } else {
                    $conditions[] = DB::prepare("(DATE($full_filed_name) = ?)", $today->advance(-86400, false));
                }

                break;

            // List items that match today
            case self::DATE_FILTER_TODAY:
                if ($user_gmt_offset != 0 && $this->calculateTimezoneWhenFilteringByDate($field_name)) {
                    $now = DateValue::makeFromString($today->format('Y-m-d'));

                    [$from, $to] = $this->getGmtRangeForDate($now, $user_gmt_offset);

                    $conditions[] = DB::prepare("($full_filed_name BETWEEN ? AND ?)", $from, $to);
                } else {
                    $conditions[] = DB::prepare("(DATE($full_filed_name) = ?)", $today);
                }

                break;

            // List items that match tomorrow
            case self::DATE_FILTER_TOMORROW:
                $conditions[] = DB::prepare("(DATE($full_filed_name) = ?)", $today->advance(86400, false));

                break;

            // List items that match previous week
            case self::DATE_FILTER_LAST_WEEK:
                $first_week_day = ConfigOptions::getValueFor('time_first_week_day', $user);

                $seven_days_ago = $today->advance(-604800, false);

                $conditions[] = DB::prepare("($full_filed_name >= ? AND $full_filed_name <= ?)", $seven_days_ago->beginningOfWeek($first_week_day), $seven_days_ago->endOfWeek($first_week_day));

                break;

            // List items that match this week
            case self::DATE_FILTER_THIS_WEEK:
                $first_week_day = ConfigOptions::getValueFor('time_first_week_day', $user);

                $conditions[] = DB::prepare("($full_filed_name >= ? AND $full_filed_name <= ?)", $today->beginningOfWeek($first_week_day), $today->endOfWeek($first_week_day));

                break;

            // List items that match next week
            case self::DATE_FILTER_NEXT_WEEK:
                $first_week_day = ConfigOptions::getValueFor('time_first_week_day', $user);

                $next_week = $today->advance(604800, false);

                $conditions[] = DB::prepare("($full_filed_name >= ? AND $full_filed_name <= ?)", $next_week->beginningOfWeek($first_week_day), $next_week->endOfWeek($first_week_day));

                break;

            // List items that match this motnh
            case self::DATE_FILTER_LAST_MONTH:
                $month = $today->getMonth() - 1;
                $year = $today->getYear();

                if ($month == 0) {
                    $month = 12;
                    --$year;
                }

                if ($user_gmt_offset != 0 && $this->calculateTimezoneWhenFilteringByDate($field_name)) {
                    [$from, $to] = $this->getGmtRangeForDateRange(DateTimeValue::beginningOfMonth($month, $year), DateTimeValue::endOfMonth($month, $year), $user_gmt_offset);
                } else {
                    $from = DateTimeValue::beginningOfMonth($month, $year);
                    $to = DateTimeValue::endOfMonth($month, $year);
                }

                $conditions[] = DB::prepare("($full_filed_name >= ? AND $full_filed_name <= ?)", $from, $to);

                break;

            // List items that match this month
            case self::DATE_FILTER_THIS_MONTH:
                if ($user_gmt_offset != 0 && $this->calculateTimezoneWhenFilteringByDate($field_name)) {
                    [$from, $to] = $this->getGmtRangeForDateRange(DateTimeValue::beginningOfMonth($today->getMonth(), $today->getYear()), DateTimeValue::endOfMonth($today->getMonth(), $today->getYear()), $user_gmt_offset);
                } else {
                    $from = DateTimeValue::beginningOfMonth($today->getMonth(), $today->getYear());
                    $to = DateTimeValue::endOfMonth($today->getMonth(), $today->getYear());
                }

                $conditions[] = DB::prepare("($full_filed_name >= ? AND $full_filed_name <= ?)", $from, $to);

                break;

            // List items that match the next month
            case self::DATE_FILTER_NEXT_MONTH:
                $month = $today->getMonth() + 1;
                $year = $today->getYear();

                if ($month == 13) {
                    $month = 1;
                    ++$year;
                }

                $conditions[] = DB::prepare("($full_filed_name >= ? AND $full_filed_name <= ?)", DateTimeValue::beginningOfMonth($month, $year), DateTimeValue::endOfMonth($month, $year));

                break;

            // In year variations
            case self::DATE_FILTER_THIS_YEAR:
                $conditions[] = DB::prepare("(YEAR($full_filed_name) = ?)", $today->getYear());

                break;
            case self::DATE_FILTER_LAST_YEAR:
                $conditions[] = DB::prepare("(YEAR($full_filed_name) = ?)", $today->getYear() - 1);

                break;
            case self::DATE_FILTER_SELECTED_YEAR:
                $conditions[] = DB::prepare("(YEAR($full_filed_name) = ?)", $this->$in_year_getter());

                break;

            // Age is
            case self::DATE_FILTER_AGE_IS:
                $age = (int) $this->$age_getter();

                $today = $today->advance(-1 * $age * 86400, false);
                $today = DateValue::makeFromString($today->format('Y-m-d'));

                [$from, $to] = $this->getGmtRangeForDate($today, $user_gmt_offset);

                $conditions[] = DB::prepare("($full_filed_name BETWEEN ? AND ?)", $from, $to);

                break;

                break;

            // Age is less than
            case self::DATE_FILTER_AGE_IS_LESS_THAN:
                $age = (int) $this->$age_getter();

                $today = $today->advance(-1 * $age * 86400, false);
                $today = DateValue::makeFromString($today->format('Y-m-d'));

                [$from, $to] = $this->getGmtRangeForDate($today, $user_gmt_offset);

                $conditions[] = DB::prepare("($full_filed_name > ?)", $to);

                break;

                break;

            // Age is more than
            case self::DATE_FILTER_AGE_IS_MORE_THAN:
                $age = (int) $this->$age_getter();

                $today = $today->advance(-1 * $age * 86400, false);
                $today = DateValue::makeFromString($today->format('Y-m-d'));

                [$from, $to] = $this->getGmtRangeForDate($today, $user_gmt_offset);

                $conditions[] = DB::prepare("($full_filed_name < ?)", $from);

                break;

                break;

            // Specific date
            case self::DATE_FILTER_SELECTED_DATE:
                if ($user_gmt_offset != 0 && $this->calculateTimezoneWhenFilteringByDate($field_name)) {
                    [$from, $to] = $this->getGmtRangeForDate($this->$on_date_getter(), $user_gmt_offset);

                    $conditions[] = DB::prepare("($full_filed_name BETWEEN ? AND ?)", $from, $to);
                } else {
                    $conditions[] = DB::prepare("(DATE($full_filed_name) = ?)", $this->$on_date_getter());
                }

                break;

            // Before specific date
            case self::DATE_FILTER_BEFORE_SELECTED_DATE:
                if ($user_gmt_offset != 0 && $this->calculateTimezoneWhenFilteringByDate($field_name)) {
                    [$from, $to] = $this->getGmtRangeForDate($this->$on_date_getter(), $user_gmt_offset);

                    $conditions[] = DB::prepare("($full_filed_name < ?)", $from);
                } else {
                    $conditions[] = DB::prepare("(DATE($full_filed_name) < ?)", $this->$on_date_getter());
                }

                break;

            // Before and including specific date
            case self::DATE_FILTER_BEFORE_AND_ON_SELECTED_DATE:
                if ($user_gmt_offset != 0 && $this->calculateTimezoneWhenFilteringByDate($field_name)) {
                    [$from, $to] = $this->getGmtRangeForDate($this->$on_date_getter(), $user_gmt_offset);

                    $conditions[] = DB::prepare("($full_filed_name <= ?)", $from);
                } else {
                    $conditions[] = DB::prepare("(DATE($full_filed_name) <= ?)", $this->$on_date_getter());
                }

                break;

            // After specific date
            case self::DATE_FILTER_AFTER_SELECTED_DATE:
                if ($user_gmt_offset != 0 && $this->calculateTimezoneWhenFilteringByDate($field_name)) {
                    [$from, $to] = $this->getGmtRangeForDate($this->$on_date_getter(), $user_gmt_offset);

                    $conditions[] = DB::prepare("($full_filed_name > ?)", $to);
                } else {
                    $conditions[] = DB::prepare("(DATE($full_filed_name) > ?)", $this->$on_date_getter());
                }

                break;

            // After and including specific date
            case self::DATE_FILTER_AFTER_AND_ON_SELECTED_DATE:
                if ($user_gmt_offset != 0 && $this->calculateTimezoneWhenFilteringByDate($field_name)) {
                    [$from, $to] = $this->getGmtRangeForDate($this->$on_date_getter(), $user_gmt_offset);

                    $conditions[] = DB::prepare("($full_filed_name >= ?)", $to);
                } else {
                    $conditions[] = DB::prepare("(DATE($full_filed_name) >= ?)", $this->$on_date_getter());
                }

                break;

            // Specific range
            case self::DATE_FILTER_SELECTED_RANGE:
                /**
                 * @var $from DateValue
                 * @var $to   DateValue
                 */
                [
                    $from,
                    $to
                ] = $this->$in_range_getter();

                if ($user_gmt_offset != 0 && $this->calculateTimezoneWhenFilteringByDate($field_name)) {
                    [
                        $from_gmt,
                        $to_gmt
                    ] = $this->getGmtRangeForDateRange($from, $to, $user_gmt_offset);

                    $conditions[] = DB::prepare("($full_filed_name >= ? AND $full_filed_name <= ?)", $from_gmt, $to_gmt);
                } else {
                    $conditions[] = DB::prepare("(DATE($full_filed_name) BETWEEN ? AND ?)", $from, $to);
                }

                break;

            default:
                throw new DataFilterConditionsError($date_filter, $this->$filter_getter(), 'mixed', 'Unknown date filter');
        }
    }

    /**
     * Get GMT range for Single Date.
     *
     * @param  int   $offset
     * @return array
     */
    protected function getGmtRangeForDate(DateValue $date, $offset)
    {
        $from_datetime = $date->beginningOfDay();
        $to_datetime = $date->endOfDay();

        return [
            $from_datetime->advance(-1 * $offset),
            $to_datetime->advance(-1 * $offset),
        ];
    }

    /**
     * Get GMT range for Date Range.
     *
     * @param  int   $offset
     * @return array
     */
    protected function getGmtRangeForDateRange(DateValue $from, DateValue $to, $offset)
    {
        return [
            $this->getGmtRangeForDate($from, $offset)[0],
            $this->getGmtRangeForDate($to, $offset)[1],
        ];
    }

    /**
     * Return true if we should factore in time zone when we are filtering by a given date.
     *
     * @param  string $field_name
     * @return bool
     */
    protected function calculateTimezoneWhenFilteringByDate($field_name)
    {
        switch ($field_name) {
            case 'created_on':
            case 'completed_on':
                $should_calculate = true;

                break;
            default:
                $should_calculate = false;

                break;
        }

        return $should_calculate;
    }

    /**
     * @var DateValue|null
     */
    private $today_reference;

    /**
     * Return today reference date.
     *
     * @return DateValue
     */
    public function getTodayReference()
    {
        return $this->today_reference;
    }

    /**
     * Set today reference.
     *
     * Today reference makes filter run as if it was run on a given date. This is useful for testing, because timezone
     * offsets may very during the year (DST)
     *
     * @param DateValue|null $value
     */
    public function setTodayReference($value)
    {
        if ($value instanceof DateValue || $value === null) {
            $this->today_reference = $value;
        } else {
            throw new InvalidInstanceError('value', $value, 'DateValue');
        }
    }

    // ---------------------------------------------------
    //  CSV Export
    // ---------------------------------------------------

    /**
     * Path of the work CSV file.
     *
     * @var string
     */
    private $csv_export_file_path;

    /**
     * Write handle on the CSV export.
     *
     * @var resource
     */
    private $export_handle = null;

    /**
     * Returns true if export has been started.
     */
    private function isExportStarted(): bool
    {
        return $this->export_handle !== null;
    }

    /**
     * Return export handle.
     *
     * @return resource
     */
    private function getExportHandle()
    {
        return $this->export_handle;
    }

    protected function beginExport(array $columns): void
    {
        if ($this->isExportStarted()) {
            throw new DataFilterExportError(DataFilter::EXPORT_ERROR_ALREADY_STARTED);
        }

        $this->csv_export_file_path = AngieApplication::getAvailableWorkFileName(
            $this->getExportFileName(),
            'csv'
        );

        $this->export_handle = fopen($this->csv_export_file_path, 'w');

        if (!$this->export_handle) {
            throw new DataFilterExportError(DataFilter::EXPORT_ERROR_CANT_OPEN_HANDLE);
        }

        fwrite($this->export_handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv(
            $this->export_handle,
            $columns,
            defined('DEFAULT_CSV_SEPARATOR') ? DEFAULT_CSV_SEPARATOR : ','
        );
    }

    protected function exportWriteLine(array $elements): void
    {
        if (!$this->isExportStarted()) {
            throw new DataFilterExportError(DataFilter::EXPORT_ERROR_HANDLE_NOT_OPEN);
        }

        // Fix CSV Excel Macro Injection also known as CEMI
        // by prepend single quote before potential function
        foreach ($elements as $element_name => &$value) {
            if ($value !== null && !is_scalar($value)) {
                AngieApplication::log()->error(
                    'Value sent to CSV encoder is not a scalar',
                    [
                        'data_filter_type' => get_class($this),
                        'element_name' => $element_name,
                        'value' => $value,
                        'full_row' => $elements,
                        'var_type' => gettype($value),
                    ]
                );
            }

            if (preg_match('/^[\+|\=|\-|\@]/', $value)) {
                $value = "'" . $value;
            }
        }

        fputcsv(
            $this->export_handle,
            $elements,
            defined('DEFAULT_CSV_SEPARATOR') ? DEFAULT_CSV_SEPARATOR : ','
        );
    }

    protected function completeExport(): string
    {
        if (!$this->isExportStarted()) {
            throw new DataFilterExportError(DataFilter::EXPORT_ERROR_HANDLE_NOT_OPEN);
        }
        $file_path = $this->csv_export_file_path;

        fclose($this->export_handle);

        $this->export_handle = null;
        $this->csv_export_file_path = null;

        return $file_path;
    }

    public function canView(User $user): bool
    {
        return $user->canUseReports();
    }

    public function canEdit(User $user): bool
    {
        return $this->isCreatedBy($user) || DataFilters::canManage(get_class($this), $user);
    }

    public function canDelete(User $user): bool
    {
        return $this->isCreatedBy($user) || DataFilters::canManage(get_class($this), $user);
    }

    // ---------------------------------------------------
    //  System
    // ---------------------------------------------------

    /**
     * Validate before save.
     */
    public function validate(ValidationErrors & $errors)
    {
        if ($this->validatePresenceOf('name')) {
            if (!$this->validateUniquenessOf('name', 'type')) {
                $errors->addError('Name needs to be unique', 'name');
            }
        } else {
            $errors->addError('Name is required', 'name');
        }
    }

    /**
     * Return true if a before/after filters should include the date.
     *
     * @param $value
     * @return bool
     */
    private function isFilterInclusive($value)
    {
        return (bool) strpos($value, 'inclusive') !== false;
    }
}
