<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class FwCalendar extends BaseCalendar implements IConfigContext
{
    /**
     * Default calendar color.
     */
    const DEFAULT_COLOR = '#63A7DE';

    public function getHistoryFields(): array
    {
        return array_merge(
            parent::getHistoryFields(),
            [
                'name',
                'color',
            ]
        );
    }

    public function canView(User $user): bool
    {
        return $user->isOwner() || $this->isCreatedBy($user) || $this->isMember($user);
    }

    public function canEdit(User $user): bool
    {
        return $user->isOwner() || $this->isCreatedBy($user);
    }

    public function canDelete(User $user): bool
    {
        return $user->isOwner() || $this->isCreatedBy($user);
    }

    // ---------------------------------------------------
    //  Context
    // ---------------------------------------------------

    public function getRoutingContext(): string
    {
        return 'calendar';
    }

    public function getRoutingContextParams(): array
    {
        return [
            'calendar_id' => $this->getId(),
        ];
    }

    protected function getCalendarFeedElements(IUser $user)
    {
        /** @var DBResult|CalendarEvent[] $calendar_events */
        $calendar_events = CalendarEvents::getByCalendar($this);

        return !empty($calendar_events) ? $calendar_events->toArray() : [];
    }

    public function getCalendarElementSummarySufix()
    {
        return ' (' . $this->getName() . ')';
    }

    public function jsonSerialize(): array
    {
        $result = parent::jsonSerialize();

        $result['color'] = $this->getColor();
        $result['created_by_id'] = $this->getCreatedById();
        $result['created_by_name'] = $this->getCreatedByName();

        return $result;
    }

    // ---------------------------------------------------
    //  Events delegate
    // ---------------------------------------------------

    /**
     * Add calendar event to the parent object.
     *
     * There are two ways that this function can be called:
     *
     * First is by providing event instance as $p1. In that case, $p2 is treated as save indicator. Example:
     *
     * $calendar->calendarEvents->add(new CalendarEvent(), true);
     *
     * Second is by providing event name, event date (single value or range as array) and save indicator. Example:
     *
     * $calendar->calendarEvents->add("Dusan's Holiday", array('2012/05/05', '2012/05/15'), true);
     *
     * @param  mixed         $p1
     * @param  mixed         $p2
     * @param  mixed         $p3
     * @return CalendarEvent
     */
    public function addEvent($p1, $p2 = null, $p3 = null)
    {
        if ($p1 instanceof CalendarEvent) {
            return $this->addEventInstance($p1, $p2);
        } else {
            return $this->addEventFromParams($p1, $p2, $p3);
        }
    }

    /**
     * Add event instance to the parent object.
     *
     * @param  bool          $save
     * @return CalendarEvent
     */
    private function addEventInstance(CalendarEvent $event, $save = false)
    {
        $event->setCalendar($this);

        if ($save) {
            $event->save();
        }

        return $event;
    }

    /**
     * Add event based on given parameters.
     *
     * @param  string        $event_name
     * @param  mixed         $date_or_range
     * @param  bool          $save
     * @return CalendarEvent
     */
    private function addEventFromParams($event_name, $date_or_range, $save = false)
    {
        [$starts_on, $ends_on] = CalendarEvents::dateOrRangeToRange($date_or_range);

        $event = new CalendarEvent();
        $event->setCalendar($this);

        $event->setName($event_name);
        $event->setStartsOn($starts_on);
        $event->setEndsOn($ends_on);

        if ($save) {
            $event->save();
        }

        return $event;
    }

    /**
     * Return number of events associated with parent object that $user can see.
     *
     * @return int
     */
    public function countEvents()
    {
        return CalendarEvents::count(DB::prepare('calendar_id = ?', $this->getId()));
    }

    /**
     * Return number of events for a given date or date range, associated with parent object that $user can see.
     *
     * @param $date_or_range
     * @return int
     */
    public function countEventsFor($date_or_range)
    {
        return CalendarEvents::countFor($date_or_range, ['calendar_id = ?', $this->getId()]);
    }

    // ---------------------------------------------------
    //  Trash
    // ---------------------------------------------------
    /**
     * Move to trash.
     *
     * @param  bool      $bulk
     * @throws Exception
     */
    public function moveToTrash(User $by = null, $bulk = false)
    {
        try {
            DB::beginWork('Begin: move project to trash @ ' . __CLASS__);

            // @todo move to trash calendar events
            DB::execute('UPDATE calendar_events SET original_is_trashed = ? WHERE calendar_id = ? AND is_trashed = ?', true, $this->getId(), true); // Remember original is_trashed flag for already trashed elements

            /** @var ITrash[] $calendar_events */
            if ($calendar_events = CalendarEvents::find(['conditions' => ['calendar_id = ? AND is_trashed = ?', $this->getId(), false]])) {
                foreach ($calendar_events as $calendar_event) {
                    $calendar_event->moveToTrash($by, true);
                }
            }

            parent::moveToTrash($by, $bulk);

            DB::commit('Done: move project to trash @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: move project to trash @ ' . __CLASS__);
            throw $e;
        }
    }

    /**
     * Restore from trash.
     *
     * @param  bool      $bulk
     * @throws Exception
     */
    public function restoreFromTrash($bulk = false)
    {
        try {
            DB::beginWork('Begin: restore calendar from trash @ ' . __CLASS__);

            /** @var ITrash[] $calendar_events */
            if ($calendar_events = CalendarEvents::find(['conditions' => ['calendar_id = ? AND is_trashed = ? AND original_is_trashed = ?', $this->getId(), true, false]])) {
                foreach ($calendar_events as $calendar_event) {
                    $calendar_event->restoreFromTrash(true);
                }
            }

            DB::execute('UPDATE calendar_events SET is_trashed = ?, original_is_trashed = ? WHERE calendar_id = ? AND original_is_trashed = ?', true, false, $this->getId(), true); // Restore previously trashed elements as trashed

            parent::restoreFromTrash($bulk);

            DB::commit('Done: restore project from trash @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: restore project from trash @ ' . __CLASS__);
            throw $e;
        }
    }

    /**
     * Delete calendar and all related data.
     *
     * @param  bool      $bulk
     * @throws Exception
     */
    public function delete($bulk = false)
    {
        try {
            DB::beginWork('Deleting calendar @ ' . __CLASS__);

            $this->untouchable(function () {
                CalendarEvents::deleteByCalendar($this);
            });

            parent::delete($bulk);

            DB::commit('Calendar deleted @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Failed to delete calendar @ ' . __CLASS__);
            throw $e;
        }
    }

    // ---------------------------------------------------
    //  System
    // ---------------------------------------------------

    /**
     * Validate before save.
     */
    public function validate(ValidationErrors & $errors)
    {
        $this->validatePresenceOf('name') or $errors->fieldValueIsRequired('name');
        $this->validatePresenceOf('color') or $errors->fieldValueIsRequired('color');

        parent::validate($errors);
    }
}
