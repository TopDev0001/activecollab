<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

final class SinceLastVisitService implements SinceLastVisitServiceInterface
{
    /**
     * @var IUser
     */
    private $user;

    public function __construct(IUser $user)
    {
        $this->user = $user;
    }

    public function getLastVisitTimestamp(DataObject $object, $delay = null)
    {
        if (!($object instanceof IAccessLog)) {
            throw new LogicException(
                sprintf(
                    '%s object is not instance of %s class',
                    get_class($object),
                    IAccessLog::class
                )
            );
        }

        $delay = $delay === null ? self::LAST_VISIT_DELAY : $delay;

        $accessed_on = DB::executeFirstCell(
            'SELECT MAX(accessed_on) as "access_on"
              FROM access_logs
               WHERE ' . AccessLogs::parentToCondition($object) . ' AND accessed_by_id = ? AND accessed_on <= ?',
            $this->user->getId(),
            DateTimeValue::makeFromTimestamp(DateTimeValue::now()->getTimestamp() - $delay)->toMySQL()
        );

        return $accessed_on ? DateTimeValue::makeFromString($accessed_on)->getTimestamp() : null;
    }
}
