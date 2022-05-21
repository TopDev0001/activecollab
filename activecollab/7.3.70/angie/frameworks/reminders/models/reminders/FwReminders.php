<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ReminderEvents\ReminderCreatedEvent;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ReminderEvents\ReminderDeletedEvent;
use Angie\Utils\SystemDateResolver\SystemDateResolverInterface;

abstract class FwReminders extends BaseReminders
{
    /**
     * Return new collection.
     *
     * @param  User|null       $user
     * @return ModelCollection
     */
    public static function prepareCollection(string $collection_name, $user)
    {
        if (str_starts_with($collection_name, 'reminders_for')) {
            [$parent, $for] = Reminders::parentAndUserFromCollectionName($collection_name);

            if ($parent instanceof IReminders && $for instanceof User && $for->getId() === $user->getId()) {
                $collection = parent::prepareCollection($collection_name, $user);
                $collection->setConditions('parent_type = ? AND parent_id = ? AND created_by_id = ?', get_class($parent), $parent->getId(), $for->getId());

                return $collection;
            }
        }

        throw new InvalidParamError('collection_name', $collection_name);
    }

    /**
     * Get parent and user from reminders collection name.
     *
     * @param  string       $collection_name
     * @return DataObject[]
     */
    private static function parentAndUserFromCollectionName($collection_name)
    {
        $bits = explode('_', $collection_name);

        [$parent_type, $parent_id] = explode('-', array_pop($bits));

        if ($parent_type && $parent_id) {
            $parent = DataObjectPool::get($parent_type, $parent_id);
        } else {
            $parent = null;
        }

        array_pop($bits); // Remove _in_

        return [$parent, DataObjectPool::get('User', array_pop($bits))];
    }

    public static function create(
        array $attributes,
        bool $save = true,
        bool $announce = true
    ): Reminder
    {
        try {
            DB::beginWork('Creating a reminder');

            $reminder = parent::create($attributes, $save, $announce); // @TODO Announcement should be sent after we verify that reminder has subscribers

            if ($reminder->isLoaded() && !$reminder->countSubscribers()) {
                throw new ValidationErrors(['subscribers' => 'No subscribers specified']);
            }

            DataObjectPool::announce(new ReminderCreatedEvent($reminder));

            DB::commit('Reminder created');

            return $reminder;
        } catch (Exception $e) {
            DB::rollback('Failed to create a reminder');
            throw $e;
        }
    }

    public static function send()
    {
        $system_date = AngieApplication::getContainer()
            ->get(SystemDateResolverInterface::class)
                ->getSystemDate();

        $reminders = Reminders::findDueForSend($system_date);

        if ($reminders) {
            AngieApplication::log()->info(
                'Sending {number_of_reminders} for {day}',
                [
                    'number_of_reminders' => count($reminders),
                    'day' => $system_date->format('Y-m-d'),
                    'utc_timestamp' => date('Y-m-d H:i:s'),
                ]
            );

            foreach ($reminders as $reminder) {
                $reminder->preventTouchOnNextDelete();
                $reminder->send();
                $reminder->delete();
            }
        }
    }

    /**
     * Return all reminders that need to be sent on the given date.
     *
     * @return Reminder[]|DBResult
     */
    public static function findDueForSend(DateValue $date)
    {
        return Reminders::findBy('send_on', $date);
    }

    public static function deleteByUser(User $user)
    {
        DB::transact(
            function () use ($user) {
                if ($reminders = Reminders::findBy('created_by_id', $user->getId())) {
                    foreach ($reminders as $reminder) {
                        $reminder->delete();
                    }
                }
            }
        );
    }

    public static function remove($reminder)
    {
        DataObjectPool::announce(new ReminderDeletedEvent($reminder));

        return self::scrap($reminder);
    }
}
