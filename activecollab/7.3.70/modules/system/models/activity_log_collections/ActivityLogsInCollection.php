<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class ActivityLogsInCollection extends CompositeCollection
{
    use IWhosAsking;
    use IActivityLogsCollection;

    public function getModelName(): string
    {
        return $this->in->getModelName();
    }

    /**
     * @var ApplicationObject|IUpdatedOn
     */
    protected $in;

    /**
     * Set who the logs are for.
     *
     * @return $this
     * @throws InvalidParamError
     */
    public function &setIn(ApplicationObject $in)
    {
        if ($in instanceof ApplicationObject && $in instanceof IUpdatedOn) {
            $this->in = $in;
        } else {
            throw new InvalidParamError('in', $in, 'ApplicationObject');
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
        return sha1($this->in->getUpdatedOn()->toMySQL() . ',' . $this->getActivityLogsCollection()->getTimestampHash('updated_on'));
    }

    /**
     * @var ModelCollection
     */
    private $activity_logs_collection;

    /**
     * Return assigned tasks collection.
     *
     * @return ModelCollection
     * @throws ImpossibleCollectionError
     */
    protected function &getActivityLogsCollection()
    {
        if (empty($this->activity_logs_collection)) {
            if ($this->in instanceof ApplicationObject && $this->getWhosAsking() instanceof User) {
                $this->activity_logs_collection = ActivityLogs::prepareCollection('activity_logs_in_' . get_class($this->in) . '-' . $this->in->getId() . '_page_' . $this->getCurrentPage(), $this->getWhosAsking());
            } else {
                throw new ImpossibleCollectionError("Invalid in and/or who's asking instance");
            }
        }

        return $this->activity_logs_collection;
    }
}
