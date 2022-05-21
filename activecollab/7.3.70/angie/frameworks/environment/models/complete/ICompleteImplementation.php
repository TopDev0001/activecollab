<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Search\SearchItem\SearchItemInterface as SearchItem;

trait ICompleteImplementation
{
    public function registerICompleteImplementation()
    {
        $this->registerEventHandler('on_json_serialize', function (array & $result) {
            if ($this->isCompleted()) {
                $result['completed_on'] = $this->getCompletedOn();
                $result['completed_by_id'] = $this->getCompletedById();
            } else {
                $result['completed_on'] = null;
                $result['completed_by_id'] = null;
            }

            $result['is_completed'] = $result['completed_on'] instanceof DateTimeValue;
        });
    }

    /**
     * Returns true if this object is marked as completed.
     *
     * @return bool
     */
    public function isCompleted()
    {
        return $this->getCompletedOn() instanceof DateValue;
    }

    /**
     * Returns true if this object is open (not completed).
     *
     * @return bool
     */
    public function isOpen()
    {
        return !$this->isCompleted();
    }

    /**
     * Return true if $user can change completion status.
     *
     * @return bool
     */
    public function canChangeCompletionStatus(User $user)
    {
        return $this->canEdit($user);
    }

    public function complete(User $by, bool $bulk = false)
    {
        if ($this->isOpen()) {
            // using this instead of call_user_func() because we need to pass $this by reference
            call_user_func_array(
                $this->getModelName() . '::update',
                [
                    &$this,
                    [
                        'completed_by_id' => $by->getId(),
                        'completed_by_email' => $by->getEmail(),
                        'completed_by_name' => $by->getName(),
                        'completed_on' => DateTimeValue::now(),
                    ],
                ]
            );

            if ($this instanceof SearchItem) {
                AngieApplication::search()->update($this, $bulk);
            }
        }
    }

    public function open(User $by, bool $bulk = false)
    {
        if ($this->isCompleted()) {
            call_user_func_array(
                $this->getModelName() . '::update',
                [
                    &$this,
                    [
                        'completed_by_id' => null,
                        'completed_by_email' => null,
                        'completed_by_name' => null,
                        'completed_on' => null,
                    ],
                ]
            );

            if ($this instanceof SearchItem) {
                AngieApplication::search()->update($this, $bulk);
            }
        }
    }

    /**
     * Return user who completed this object.
     *
     * @return IUser|null
     */
    public function getCompletedBy()
    {
        return $this->getUserFromFieldSet('completed_by');
    }

    /**
     * Set person who completed this object.
     *
     * @param  mixed $completed_by
     * @return mixed
     */
    private function setCompletedBy($completed_by)
    {
        return $this->setUserFromFieldSet($completed_by, 'completed_by');
    }

    // ---------------------------------------------------
    //  Expectations
    // ---------------------------------------------------

    /**
     * Return value of completed_on field.
     *
     * @return DateTimeValue
     */
    abstract public function getCompletedOn();

    /**
     * Set value of completed_on field.
     *
     * @param  DateTimeValue $value
     * @return DateTimeValue
     */
    abstract public function setCompletedOn($value);

    /**
     * Return value of completed_by_id field.
     *
     * @return int
     */
    abstract public function getCompletedById();

    /**
     * Return true if $user can update parent object.
     */
    abstract public function canEdit(User $user): bool;

    abstract protected function registerEventHandler(string $event, callable $handler): void;

    /**
     * Returns user instance (or NULL) for given field set.
     *
     * @param  string $field_set_prefix
     * @return IUser
     */
    abstract public function getUserFromFieldSet($field_set_prefix);

    /**
     * Set by user for given field set.
     *
     * @param  IUser                   $by_user
     * @param  string                  $field_set_prefix
     * @param  bool                    $optional
     * @param  bool                    $can_be_anonymous
     * @return User|AnonymousUser|null
     * @throws InvalidInstanceError
     */
    abstract public function setUserFromFieldSet($by_user, $field_set_prefix, $optional = true, $can_be_anonymous = true);

    abstract public function getModelName(
        bool $underscore = false,
        bool $singular = false
    ): string;
}
