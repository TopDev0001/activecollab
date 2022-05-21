<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateFixCalendarEventsRepeatColumn extends AngieModelMigration
{
    public function up()
    {
        $repeat_event_options = [
            'dont',
            'daily',
            'weekly',
            'monthly',
            'yearly',
        ];

        $calendar_events = $this->useTableForAlter('calendar_events');

        $this->fixEmptyRepeatEventValues($calendar_events, $repeat_event_options);

        $calendar_events->alterColumn(
            'repeat_event',
            new DBEnumColumn(
                'repeat_event',
                $repeat_event_options,
                'dont'
            )
        );
    }

    private function fixEmptyRepeatEventValues(DBTable $calendar_events, array $repeat_event_options): void
    {
        $current_repeat_events_column = $calendar_events->getColumn('repeat_event');

        if ($current_repeat_events_column instanceof DBEnumColumn) {
            $current_possibilities = $this->getRepeatEventPossibilities();

            if (!in_array('dont', $current_possibilities)) {
                $current_repeat_events_column->setPossibilities(
                    array_merge(
                        [
                            'dont',
                        ],
                        $current_possibilities
                    )
                );

                $calendar_events->alterColumn(
                    'repeat_event',
                    $current_repeat_events_column
                );
            }
        }

        $this->execute(
            'UPDATE `calendar_events` SET `repeat_event` = ? WHERE `repeat_event` IS NULL OR `repeat_event` NOT IN (?)',
            'dont',
            $repeat_event_options
        );
    }

    /**
     * We can't use DBEnumColumn::getPossibilities() because it returned empty array on some servers in the wild!
     */
    private function getRepeatEventPossibilities(): array
    {
        $repeat_events_declaration = $this->executeFirstRow(
            'SHOW COLUMNS FROM `calendar_events` LIKE ?', 'repeat_event'
        );

        if (!empty($repeat_events_declaration)) {
            preg_match('/enum\((.*)\)$/', $repeat_events_declaration['Type'], $matches);
            $possibilities = str_getcsv($matches[1]);

            if (is_array($possibilities)) {
                $possibilities = array_map(
                    function ($possibility) {
                        return trim($possibility, "'");
                    },
                    $possibilities
                );

                return $possibilities;
            }
        }

        return [];
    }
}
