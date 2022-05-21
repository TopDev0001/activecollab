<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Urls\Router\RouterInterface;

/**
 * Morning paper snapshot.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
class MorningPaperSnapshot
{
    /**
     * Parsed snapshot data.
     *
     * @var array
     */
    private $data;

    /**
     * Cached array of event ID-s that current user is subscribed to.
     *
     * @var array
     */
    private $user_calendar_event_subscriptions = false;

    /**
     * Task URL pattern.
     *
     * @var string
     */
    private $project_url_pattern;

    /**
     * User URL pattern.
     *
     * @var string
     */
    private $user_url_pattern;

    /**
     * Create a new snapshot instance.
     *
     * @param  string|array      $data
     * @throws InvalidParamError
     */
    public function __construct($data)
    {
        if (is_string($data)) {
            $this->data = json_decode(file_get_contents($data), true);
        } elseif (is_array($data)) {
            $this->data = $data;
        } else {
            throw new InvalidParamError('data', $data, 'Snapshot data missing');
        }
    }

    /**
     * Return previous day timestamp.
     *
     * @return string
     */
    public function getPreviousDay()
    {
        return $this->data['prev_business_day']['date'];
    }

    /**
     * Return unfiltered snapshot data.
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Return events by event type.
     *
     * @param  string $event_type
     * @param  bool   $search_pbd
     * @param  bool   $search_today
     * @param  bool   $search_late
     * @param  string $from
     * @return array
     */
    public function getEventsByType($event_type, $search_pbd = true, $search_today = true, $search_late = true, $from = 'events')
    {
        $result = [];

        if ($search_pbd) {
            if (isset($this->data['prev_business_day'][$from])) {
                foreach ($this->data['prev_business_day'][$from] as $events) {
                    foreach ($events as $event) {
                        if ($event['event'] == $event_type) {
                            $result[] = $event;
                        }
                    }
                }
            }
        }

        if ($search_today) {
            if (isset($this->data['today'][$from])) {
                foreach ($this->data['today'][$from] as $events) {
                    foreach ($events as $event) {
                        if ($event['event'] == $event_type) {
                            $result[] = $event;
                        }
                    }
                }
            }
        }

        if ($search_late) {
            if (isset($this->data['late'][$from])) {
                foreach ($this->data['late'][$from] as $events) {
                    foreach ($events as $event) {
                        if ($event['event'] == $event_type) {
                            $result[] = $event;
                        }
                    }
                }
            }
        }

        return $result;
    }

    public function getDataFor(User $user, bool $include_all = false): array
    {
        $prev_data = null;
        $today_data = null;
        $late_data = null;

        $project_ids = $this->getProjectIdsFor($user, $include_all);
        $calendar_ids = $this->getCalendarIdsFor($user);
        $user_ids = $user->getVisibleUserIds();

        if ($project_ids || $calendar_ids || $user_ids) {
            $project_names = $project_ids ? Projects::getIdNameMapByIds($project_ids) : [];

            if ($project_ids) {
                $late_data = $this->getLateDataFor($user->getId(), $project_ids, $project_names);
            }

            $calendar_names = $calendar_ids ? Calendars::getIdNameMapByIds($calendar_ids) : [];
            $user_names = $user_ids ? Users::getIdNameMap($user_ids) : [];

            $today_data = $this->getTodayDataFor($user, $project_ids, $project_names, $calendar_ids, $calendar_names, $user_ids, $user_names);

            if ($project_ids) {
                $prev_data = $this->getPreviousBusinessDayDataFor($project_ids, $project_names);
            }
        }

        return [
            $late_data,
            $today_data,
            $prev_data,
        ];
    }

    /**
     * Return projects ID-s and project permissions for a given user.
     *
     * @param  bool  $include_all
     * @return array
     */
    public function getProjectIdsFor(User $user, $include_all)
    {
        if ($user->isOwner()) {
            return Projects::findIdsByUser($user, $include_all, DB::prepare('is_trashed = ?', false));
        } else {
            return DB::executeFirstColumn('SELECT projects.id FROM projects, project_users WHERE projects.id = project_users.project_id AND project_users.user_id = ? AND projects.is_trashed = ?', $user->getId(), false);
        }
    }

    /**
     * Return calendar ID-s for a given user.
     *
     * @return array
     */
    public function getCalendarIdsFor(User $user)
    {
        return Calendars::findIdsByUser($user, DB::prepare('is_trashed = ?', false));
    }

    /**
     * Extract late data from the list.
     *
     * @param  int   $user_id
     * @return array
     */
    private function getLateDataFor($user_id, array $project_ids, array $project_names)
    {
        $result = [
            'late_tasks_count' => 0,
            'tasks_by_project' => [],
        ];

        foreach ($this->data[MorningPaper::LATE]['events'] as $project_id => $project_events) {
            if (in_array($project_id, $project_ids)) {
                foreach ($project_events as $project_event) {
                    if ($project_event['event'] == MorningPaper::TASK_LATE && $project_event['assignee_id'] === $user_id) {
                        ++$result['late_tasks_count'];

                        if (empty($result['tasks_by_project'][$project_id])) {
                            $result['tasks_by_project'][$project_id] = [
                                'name' => isset($project_names[$project_id]) ? $project_names[$project_id] : 'Unknown',
                                'tasks' => [],
                            ];
                        }

                        $result['tasks_by_project'][$project_id]['tasks'][] = $project_event;
                    }
                }
            }
        }

        uasort($result['tasks_by_project'], function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return $result['late_tasks_count'] ? $result : null;
    }

    /**
     * Extract today data from the list.
     *
     * @param  array $project_ids
     * @param  array $calendar_ids
     * @return array
     */
    private function getTodayDataFor(User $user, $project_ids, array $project_names, $calendar_ids, array $calendar_names, $user_ids, array $user_names)
    {
        $result = [
            'due_tasks_count' => 0,
            'tasks_by_project' => [],
            'calendar_events_count' => 0,
            'calendar_events_by_calendar' => [],
            'availability_records_count' => 0,
            'availability_records' => [],
        ];

        if ($project_ids) {
            $user_id = $user->getId();

            foreach ($this->data[MorningPaper::TODAY]['events'] as $project_id => $project_events) {
                if (in_array($project_id, $project_ids)) {
                    foreach ($project_events as $project_event) {
                        if ($project_event['event'] == MorningPaper::TASK_DUE && $project_event['assignee_id'] === $user_id) {
                            ++$result['due_tasks_count'];

                            if (empty($result['tasks_by_project'][$project_id])) {
                                $result['tasks_by_project'][$project_id] = [
                                    'name' => isset($project_names[$project_id]) ? $project_names[$project_id] : 'Unknown',
                                    'tasks' => [],
                                ];
                            }

                            $result['tasks_by_project'][$project_id]['tasks'][] = $project_event;
                        }
                    }
                }
            }

            uasort($result['tasks_by_project'], function ($a, $b) {
                return strcmp($a['name'], $b['name']);
            });
        }

        if ($calendar_ids) {
            foreach ($this->data[MorningPaper::TODAY]['calendar_events'] as $calendar_id => $calendar_events) {
                foreach ($calendar_events as $calendar_event) {
                    if (in_array($calendar_id, $calendar_ids) || $this->isUserSubscribedToCalendarEvent($user, $calendar_event['id'])) {
                        ++$result['calendar_events_count'];

                        if (!isset($result['calendar_events_by_calendar'][$calendar_id])) {
                            $result['calendar_events_by_calendar'][$calendar_id] = [
                                'name' => isset($calendar_names[$calendar_id]) ? $calendar_names[$calendar_id] : 'Unknown',
                                'calendar_events' => [],
                            ];
                        }

                        $result['calendar_events_by_calendar'][$calendar_id]['calendar_events'][] = $calendar_event;
                    }
                }
            }
        }

        if ($user_ids) {
            foreach ($this->data[MorningPaper::TODAY]['availability_records'] as $user_id => $availability_records) {
                foreach ($availability_records as $record) {
                    $record_user_id = $record['user_id'];

                    if (in_array($record_user_id, $user_ids)) {
                        ++$result['availability_records_count'];

                        $result['availability_records'][] = array_merge([
                            'user_name' => isset($user_names[$record_user_id]) ? $user_names[$record_user_id] : 'Unknown User',
                            'user_link' => $this->getUserUrl($record_user_id),
                        ], $record);
                    }
                }
            }

            if ($result['availability_records_count'] > 0) {
                usort($result['availability_records'], function ($a, $b) {
                    return strcmp(strtolower($a['user_name']), strtolower($b['user_name']));
                });
            }
        }

        $this->user_calendar_event_subscriptions = false; // Reset calendar event subscription cache

        return $result['due_tasks_count'] || $result['calendar_events_count'] || $result['availability_records_count'] ? $result : null;
    }

    /**
     * Return true if $user is subscribed to $event_id.
     *
     * @param  int  $event_id
     * @return bool
     */
    private function isUserSubscribedToCalendarEvent(User $user, $event_id)
    {
        if ($this->user_calendar_event_subscriptions === false) {
            $this->user_calendar_event_subscriptions = DB::executeFirstColumn("SELECT DISTINCT parent_id FROM subscriptions WHERE parent_type = 'CalendarEvent' AND (user_id = ? OR user_email = ?)", $user->getId(), $user->getEmail());
        }

        return $this->user_calendar_event_subscriptions ? in_array($event_id, $this->user_calendar_event_subscriptions) : false;
    }

    /**
     * Extract previous business day data from the list.
     *
     * @return array
     */
    private function getPreviousBusinessDayDataFor(array $project_ids, array $project_names)
    {
        $result = [];

        foreach ($this->data[MorningPaper::PREV]['events'] as $project_id => $project_events) {
            if (in_array($project_id, $project_ids)) {
                foreach ($project_events as $project_event) {
                    if (empty($result[$project_id])) {
                        $result[$project_id] = [
                            'name' => isset($project_names[$project_id]) ? $project_names[$project_id] : 'Unknown',
                            'permalink' => $this->getProjectUrl($project_id),
                        ];
                    }

                    if (empty($result[$project_id][$project_event['event']])) {
                        $result[$project_id][$project_event['event']] = [];
                    }

                    $result[$project_id][$project_event['event']][] = $project_event;
                }
            }
        }

        if (!empty($result)) {
            uasort($result, function ($a, $b) {
                return strcmp($a['name'], $b['name']);
            });
        }

        return count($result) ? $result : null;
    }

    /**
     * Return project URL.
     *
     * @param  int    $project_id
     * @return string
     */
    private function getProjectUrl($project_id)
    {
        if (empty($this->project_url_pattern)) {
            $this->project_url_pattern = AngieApplication::getContainer()
                ->get(RouterInterface::class)
                    ->assemble(
                        'project',
                        [
                            'project_id' => '--PROJECT-ID--',
                        ]
                    );
        }

        return str_replace('--PROJECT-ID--', $project_id, $this->project_url_pattern);
    }

    /**
     * Return user URL.
     *
     * @param  int    $user_id
     * @return string
     */
    private function getUserUrl($user_id)
    {
        if (empty($this->user_url_pattern)) {
            $this->user_url_pattern = AngieApplication::getContainer()
                ->get(RouterInterface::class)
                ->assemble(
                    'user',
                    [
                        'user_id' => '--USER-ID--',
                    ]
                );
        }

        return str_replace('--USER-ID--', $user_id, $this->user_url_pattern);
    }

    /**
     * Return today events date and time boundaries.
     *
     * @return DateTimeValue[]
     */
    public function getTodayBoundaries()
    {
        if (isset($this->data['today']) && isset($this->data['today']['boundaries']) && isset($this->data['today']['boundaries']['from']) && isset($this->data['today']['boundaries']['to'])) {
            return [DateTimeValue::makeFromString($this->data['today']['boundaries']['from']), DateTimeValue::makeFromString($this->data['today']['boundaries']['to'])];
        } else {
            return [null, null];
        }
    }

    /**
     * Return previous business day date and time boundaries.
     *
     * @return DateTimeValue[]
     */
    public function getPreviousBusinessDayBoundaries()
    {
        if (isset($this->data['prev_business_day']) && isset($this->data['prev_business_day']['boundaries']) && isset($this->data['prev_business_day']['boundaries']['from']) && isset($this->data['prev_business_day']['boundaries']['to'])) {
            return [DateTimeValue::makeFromString($this->data['prev_business_day']['boundaries']['from']), DateTimeValue::makeFromString($this->data['prev_business_day']['boundaries']['to'])];
        } else {
            return [null, null];
        }
    }
}
