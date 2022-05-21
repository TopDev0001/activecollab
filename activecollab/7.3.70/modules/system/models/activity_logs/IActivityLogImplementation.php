<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ActivityLogEvents\ActivityLogCreatedEvent;
use Angie\FeatureFlags\FeatureFlagsInterface;

trait IActivityLogImplementation
{
    public function registerIActivityLogImplementation()
    {
        $this->registerEventHandler(
            'on_after_save',
            function ($is_new, $modifications) {
                if (!$this->isGagged()) {
                    $log = $is_new ? $this->getCreatedActivityLog() : $this->getUpdatedActivityLog($modifications);

                    if ($log instanceof ActivityLog) {
                        $log->save();

                        if (AngieApplication::getContainer()->get(FeatureFlagsInterface::class)->isEnabled('real_time_activity')) {
                            DataObjectPool::announce(new ActivityLogCreatedEvent($log));
                        }
                    }
                }
            }
        );

        $this->registerEventHandler(
            'on_before_delete',
            function () {
                $this->clearActivityLogs();
            }
        );
    }

    /**
     * Return instance for created activity log.
     *
     * @return ActivityLog
     */
    protected function getCreatedActivityLogInstance()
    {
        return new InstanceCreatedActivityLog();
    }

    /**
     * Prepare and return creation log entry.
     *
     * @return ActivityLog|null
     */
    protected function getCreatedActivityLog()
    {
        $log = $this->getCreatedActivityLogInstance();

        $log->setParent($this);
        $log->setParentPath($this->getObjectPath());

        if ($this instanceof ICreatedOn) {
            $log->setCreatedOn($this->getCreatedOn());
        }

        $created_by = $this instanceof ICreatedBy && $this->getCreatedBy() instanceof IUser ? $this->getCreatedBy() : AngieApplication::authentication()->getLoggedUser();

        if ($created_by instanceof IUser) {
            $log->setCreatedBy($created_by);
        }

        return $log;
    }

    /**
     * Return instance for updated activity log.
     *
     * @return ActivityLog
     */
    protected function getUpdatedActivityLogInstance()
    {
        return new InstanceUpdatedActivityLog();
    }

    /**
     * Prepare and return update log entry.
     *
     * @return ActivityLog|null
     */
    protected function getUpdatedActivityLog(array $modifications)
    {
        if ($remember = $this->getWhatIsWorthRemembering($modifications)) {
            $log = $this->getUpdatedActivityLogInstance();

            $log->setParent($this);
            $log->setParentPath($this->getObjectPath());
            $log->setModifications($remember);

            $updated_by = $this instanceof IUpdatedBy ? $this->getUpdatedBy() : null;

            if (empty($updated_by)) {
                $updated_by = AngieApplication::authentication()->getLoggedUser();
            }

            $log->setCreatedBy($updated_by);

            return $log;
        }

        return null;
    }

    /**
     * Return true if update is worth remembering.
     *
     * @return array|null
     */
    protected function getWhatIsWorthRemembering(array $modifications)
    {
        $what_is_worth_remembering = $this->whatIsWorthRemembering();

        $remember = [];

        if ($what_is_worth_remembering === true) {
            foreach ($modifications as $k => $v) {
                $remember[$k] = $v;
            }
        } elseif (is_array($what_is_worth_remembering)) {
            foreach ($what_is_worth_remembering as $field) {
                if (isset($modifications[$field])) {
                    $remember[$field] = $modifications[$field];
                }
            }
        }

        return count($remember) ? $remember : false;
    }

    /**
     * Tell us what is worh remembering.
     *
     * - TRUE - all modifications
     * - FALSE - none of the modifications
     * - STRING[] - only modifications to these fields
     *
     * @return string[]|bool
     */
    protected function whatIsWorthRemembering()
    {
        return false;
    }

    public function clearActivityLogs(): void
    {
        ActivityLogs::deleteByParent($this);
    }

    // ---------------------------------------------------
    //  Legacy
    // ---------------------------------------------------

    /**
     * Log object completion.
     *
     * @return ActivityLog
     * @throws InvalidInstanceError
     */
    public function logCompletion(IUser $by)
    {
        if ($this instanceof IComplete) {
            return ActivityLogs::log($this, $this->getActionString('completed'), $by, $this->getTarget('completed'), $this->getComment('completed'));
        } else {
            throw new InvalidInstanceError('parent', $this, 'IComplete');
        }
    }

    /**
     * Log object reopening.
     *
     * @return ActivityLog
     * @throws InvalidInstanceError
     */
    public function logReopening(IUser $by)
    {
        if ($this instanceof IComplete) {
            return ActivityLogs::log($this, $this->getActionString('reopened'), $by, $this->getTarget('reopened'), $this->getComment('reopened'));
        } else {
            throw new InvalidInstanceError('parent', $this, 'IComplete');
        }
    }

    // ---------------------------------------------------
    //  Gagging
    // ---------------------------------------------------

    /**
     * Gag indicator.
     *
     * When implementation is gagged, log events that are automatically called
     * will not create new entries
     *
     * @var bool
     */
    protected $is_gagged = false;

    public function gag(): void
    {
        $this->is_gagged = true;
    }

    public function ungag(): void
    {
        $this->is_gagged = false;
    }

    public function isGagged(): bool
    {
        return $this->is_gagged;
    }

    // ---------------------------------------------------
    //  Expectations
    // ---------------------------------------------------

    /**
     * @return string
     */
    abstract public function getObjectPath();

    abstract protected function registerEventHandler(string $event, callable $handler): void;
}
