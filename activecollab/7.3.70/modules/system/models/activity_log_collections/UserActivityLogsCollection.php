<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class UserActivityLogsCollection extends CompositeCollection
{
    use IWhosAsking;
    use IActivityLogsCollection;

    public function getModelName(): string
    {
        return 'Users';
    }

    /**
     * @var User
     */
    private $for_or_by;

    /**
     * Return for or by instance.
     *
     * @return User
     */
    protected function &getForOrBy()
    {
        return $this->for_or_by;
    }

    /**
     * Set who the logs are for.
     *
     * @return $this
     * @throws InvalidParamError
     */
    public function &setForOrBy(User $for_or_by)
    {
        if ($for_or_by instanceof User) {
            $this->for_or_by = $for_or_by;
        } else {
            throw new InvalidParamError('for_or_by', $for_or_by, 'User');
        }

        return $this;
    }

    // ---------------------------------------------------
    //  Utility methods
    // ---------------------------------------------------

    /**
     * Return timestamp hash.
     *
     * @return string
     */
    public function getTimestampHash()
    {
        return sha1($this->for_or_by->getUpdatedOn()->toMySQL() . ',' . $this->getActivityLogsCollection()->getTimestampHash('updated_on'));
    }
}
