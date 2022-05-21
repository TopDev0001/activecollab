<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class FwCalendars extends BaseCalendars
{
    public static function canAdd(User $user): bool
    {
        return true;
    }

    public static function create(
        array $attributes,
        bool $save = true,
        bool $announce = true
    ): Calendar
    {
        try {
            DB::beginWork('Creating calendar @ ' . __CLASS__);

            $calendar = parent::create($attributes, $save, $announce); // @TODO Creation should be announced after members have been added

            if ($save && $calendar instanceof Calendar && $calendar->isLoaded()) {
                $calendar->addMembers([$calendar->getCreatedBy()]);

                if (isset($attributes['members']) && is_array($attributes['members']) && !empty($attributes['members'])) {
                    $calendar->tryToAddMembersFrom($attributes);
                }
            }

            DB::commit('Calendar created @ ' . __CLASS__);

            return $calendar;
        } catch (Exception $e) {
            DB::rollback('Failed to create calendar @ ' . __CLASS__);
            throw $e;
        }
    }

    public static function &update(
        DataObject &$instance,
        array $attributes,
        bool $save = true
    ): Calendar
    {
        if ($instance instanceof Calendar) {
            try {
                DB::beginWork('Updating calendar @ ' . __CLASS__);

                parent::update($instance, $attributes, $save);

                if ($save) {
                    if ($instance->getCreatedBy() instanceof User) {
                        $instance->setMembers([$instance->getCreatedBy()]);
                    }
                    if (isset($attributes['members']) && is_array($attributes['members']) && !empty($attributes['members'])) {
                        $instance->tryToAddMembersFrom($attributes);
                    }
                }

                DB::commit('Calendar updated @ ' . __CLASS__);
            } catch (Exception $e) {
                DB::rollback('Failed to update calendar @ ' . __CLASS__);
                throw $e;
            }
        } else {
            throw new InvalidInstanceError('instance', $instance, Calendar::class);
        }

        return $instance;
    }

    public static function getIdNameMapByIds(array $ids): array
    {
        $result = [];

        if (!empty($ids)) {
            if ($rows = DB::execute('SELECT id, name FROM calendars WHERE id IN (?) ORDER BY name', $ids)) {
                foreach ($rows as $row) {
                    $result[$row['id']] = $row['name'];
                }
            }
        }

        return $result;
    }
}
