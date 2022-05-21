<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Sabre\VObject\Component\VEvent;

trait ICalendarFeedImplementation
{
    /**
     * Export calendar to iCalendar file.
     *
     * @return string
     * @throws FileCreateError
     */
    public function exportCalendarToFile(User $user)
    {
        $filename = $this->getCalendarExportFilename($user);

        if ($handle = fopen($filename, 'w')) {
            try {
                $vcalendar = $this->getVCalendarObject();

                $summary_prefix = $this->getCalendarElementSummaryPrefix();
                $summary_sufix = $this->getCalendarElementSummarySufix();

                foreach ($this->getCalendarFeedElements($user) as $calendar_feed_element) {
                    if ($this->shouldSkipCalendarFeedElement($calendar_feed_element)) {
                        continue;
                    }

                    $uid = $calendar_feed_element->getCalendarFeedUID();

                    if ($calendar_feed_element instanceof RecurringTask) {
                        $today = DateValue::now()->beginningOfDay();
                        $ghost_tasks = RecurringTasks::getRangeForCalendar(
                            [
                                $calendar_feed_element->getId(),
                            ],
                            $today,
                            DateValue::makeFromTimestamp(strtotime('+3 month', $today->getTimestamp())),
                        );

                        foreach ($ghost_tasks as $ghost_task) {
                            /** @var VEvent $vevent */
                            $vevent = $vcalendar->add('VEVENT');

                            $start_on = $ghost_task->getStartOn();
                            $due_on = $ghost_task->getDueOn();

                            $vevent->add(
                                'SUMMARY',
                                $calendar_feed_element->getCalendarFeedSummary(
                                    $user,
                                    $summary_prefix,
                                    $summary_sufix,
                                ),
                            );

                            $vevent->remove('UID');
                            $vevent->add('UID', $uid . '_' . $ghost_task->getId());

                            $dtstart = $vevent->add('DTSTART', $start_on->toICalendar());

                            $due_on->advance(86400); // +1 day
                            $dtend = $vevent->add('DTEND', $due_on->toICalendar());

                            $dtstart['VALUE'] = 'DATE';
                            $dtend['VALUE'] = 'DATE';
                        }
                    } else {
                        /** @var VEvent $vevent */
                        $vevent = $vcalendar->add('VEVENT');

                        $vevent->remove('UID');
                        $vevent->add('UID', $uid);

                        $vevent->add(
                            'SUMMARY',
                            $calendar_feed_element->getCalendarFeedSummary(
                                $user,
                                $summary_prefix,
                                $summary_sufix,
                            ),
                        );

                        if ($description = $calendar_feed_element->getCalendarFeedDescription($user)) {
                            $vevent->add('DESCRIPTION', $description);
                        }

                        if ($date_start = $calendar_feed_element->getCalendarFeedDateStart()) {
                            $dtstart = $vevent->add('DTSTART', $date_start->toICalendar());
                            if (!($date_start instanceof DateTimeValue)) {
                                $dtstart['VALUE'] = 'DATE';
                            }
                        }

                        if ($date_end = $calendar_feed_element->getCalendarFeedDateEnd()) {
                            $dtend = $vevent->add('DTEND', $date_end->toICalendar());
                            if (!($date_end instanceof DateTimeValue)) {
                                $dtend['VALUE'] = 'DATE';
                            }
                        }

                        if ($rrule = $calendar_feed_element->getCalendarFeedRepeatingRule()) {
                            $vevent->add('RRULE', $rrule);
                        }
                    }
                }

                fwrite($handle, $vcalendar->serialize());
            } finally {
                fclose($handle);
            }
        } else {
            throw new FileCreateError($filename);
        }

        return $filename;
    }

    private function shouldSkipCalendarFeedElement($element)
    {
        return !$element instanceof ICalendarFeedElement || $element->skipCalendarFeed();
    }

    public function getCalendarElementSummaryPrefix()
    {
        return '';
    }

    public function getCalendarElementSummarySufix()
    {
        return '';
    }

    /**
     * Create and return new VCalendar object instance.
     *
     * @return \Sabre\VObject\Component\VCalendar
     */
    protected function getVCalendarObject()
    {
        return new \Sabre\VObject\Component\VCalendar();
    }

    /**
     * Return calendar elements that $user has access to.
     *
     * @return ICalendarFeedElement[]
     */
    abstract protected function getCalendarFeedElements(IUser $user);

    /**
     * Return proposed calendar file name.
     *
     * For objects that implemented IUpdatedOn behavior, system will return:
     *
     * type-#CALENDAR_ID#-for-#USER_ID#-#UPDATED_ON#.ics
     *
     * If object does not implement IUpdatedOn, system will return:
     *
     * type-#CALENDAR_ID#-for-#USER_ID#.ics
     *
     * @return string
     */
    protected function getCalendarExportFilename(User $user)
    {
        $bits = [
            AngieApplication::getAccountId(),
            $this->getModelName(false, true),
            $this->getId(),
        ];

        if ($this instanceof IUpdatedOn) {
            $bits[] = $this->getUpdatedOn()->getTimestamp();
        }

        $bits[] = '-for-';
        $bits[] = $user->getId();
        $bits[] = $user->getUpdatedOn()->getTimestamp();

        return WORK_PATH . '/' . implode('-', $bits) . '.ics';
    }

    /**
     * Return object ID.
     *
     * @return int
     */
    abstract public function getId();
    abstract public function getModelName(
        bool $underscore = false,
        bool $singular = false
    ): string;
}
