<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\TrashEvents\MovedToTrashEvent;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\TrashEvents\RestoredFromTrashEvent;

trait ITrashImplementation
{
    public function registerITrashImplementation(): void
    {
        if ($this instanceof IHistory) {
            $this->addHistoryFields('is_trashed');
        }

        $this->registerEventHandler(
            'on_history_field_renderers',
            function (& $renderers) {
                $renderers['is_trashed'] = function ($old_value, $new_value, Language $language) {
                    if ($new_value) {
                        return lang('Moved to trash', null, true, $language);
                    } else {
                        return lang('Restored from trash', null, true, $language);
                    }
                };
            }
        );

        $this->registerEventHandler(
            'on_json_serialize',
            function (array & $result) {
                $result['is_trashed'] = $this->getIsTrashed();
                $result['trashed_on'] = $this->getTrashedOn();
                $result['trashed_by_id'] = $this->getTrashedById();
            }
        );
    }

    abstract protected function registerEventHandler(string $event, callable $handler): void;

    /**
     * Return value of is_trashed field.
     *
     * @return bool
     */
    abstract public function getIsTrashed();

    /**
     * Return value of trashed_on field.
     *
     * @return DateTimeValue
     */
    abstract public function getTrashedOn();

    /**
     * Get value of trashed_by_id field.
     *
     * @return int
     */
    abstract public function getTrashedById();

    // ---------------------------------------------------
    //  Expectations
    // ---------------------------------------------------
    /**
     * Move to trash.
     *
     * @param bool $bulk
     */
    public function moveToTrash(User $by = null, $bulk = false)
    {
        DB::transact(
            function () use ($by, $bulk) {
                $this->triggerEvent('on_before_move_to_trash', [&$by, $bulk]);

                if ($bulk && method_exists($this, 'setOriginalIsTrashed')) {
                    $this->setOriginalIsTrashed($this->getIsTrashed());
                }

                $this->setIsTrashed(true);
                $this->setTrashedOn(DateTimeValue::now());

                if ($by instanceof User) {
                    $this->setTrashedById($by->getId());
                } else {
                    $this->setTrashedById(AngieApplication::authentication()->getLoggedUserId());
                }

                $this->save();

                if (empty($bulk) && $this instanceof IChild) {
                    $this->getParent()->touch();
                }

                $this->triggerEvent('on_after_move_to_trash', [$bulk]);

                if (!$bulk) {
                    DataObjectPool::announce(new MovedToTrashEvent($this));
                }
            },
            'Moving object to trash'
        );
    }

    /**
     * Trigger an internal event.
     *
     * @param string $event
     * @param array  $event_parameters
     */
    abstract protected function triggerEvent($event, $event_parameters = null);

    /**
     * Set value of is_trashed field.
     *
     * @param  bool $value
     * @return bool
     */
    abstract public function setIsTrashed($value);

    /**
     * Set value of trashed_on field.
     *
     * @param  DateTimeValue $value
     * @return DateTimeValue
     */
    abstract public function setTrashedOn($value);

    /**
     * Set value of trashed_by_id field.
     *
     * @param  int $value
     * @return int
     */
    abstract public function setTrashedById($value);

    /**
     * Restore from trash.
     *
     * @param bool $bulk
     */
    public function restoreFromTrash($bulk = false)
    {
        if (!$this->getIsTrashed()) {
            return;
        }

        DB::transact(
            function () use ($bulk) {
                $this->triggerEvent('on_before_restore_from_trash', [$bulk]);

                if ($bulk && method_exists($this, 'getOriginalIsTrashed') && method_exists($this, 'setOriginalIsTrashed')) {
                    $this->setIsTrashed($this->getOriginalIsTrashed());
                    $this->setOriginalIsTrashed(false);
                } else {
                    $this->setIsTrashed(false);
                }

                $this->setTrashedOn(null);
                $this->setTrashedById(0);
                $this->save();

                if (empty($bulk) && $this instanceof IChild) {
                    $this->getParent()->touch();
                }

                $this->triggerEvent('on_after_restore_from_trash', [$bulk]);

                if (!$bulk) {
                    DataObjectPool::announce(new RestoredFromTrashEvent($this));
                }
            },
            'Moving object to trash'
        );
    }

    public function canTrash(User $user): bool
    {
        return $this->canEdit($user);
    }

    abstract public function canEdit(User $user): bool;

    /**
     * Return true if $user can restore this object from trash.
     *
     * @return bool
     */
    public function canRestoreFromTrash(User $user)
    {
        if ($this->getIsTrashed()) {
            if ($this instanceof IChild) {
                $parent = $this->getParent();

                if ($parent instanceof ITrash && $parent->getIsTrashed()) {
                    return false;
                }
            }

            return $user->isOwner() || $this->getTrashedById() == $user->getId();
        }

        return false;
    }
}
