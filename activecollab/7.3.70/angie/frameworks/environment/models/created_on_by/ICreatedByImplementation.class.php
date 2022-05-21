<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Authentication\AuthenticatedUser\AuthenticatedUserInterface;

trait ICreatedByImplementation
{
    public function registerICreatedByImplementation()
    {
        $this->registerEventHandler(
            'on_json_serialize',
            function (array & $result) {
                $result['created_by_id'] = $this->getCreatedById();
                $result['created_by_name'] = $this->getCreatedByName();
                $result['created_by_email'] = $this->getCreatedByEmail();
            }
        );

        $this->registerEventHandler(
            'on_before_save',
            function ($is_new) {
                if ($is_new && $this->isCreatedByEmpty() && AngieApplication::isAuthenticationLoaded()) {
                    $this->setCreatedBy(AngieApplication::authentication()->getAuthenticatedUser());
                }
            }
        );
    }

    private function isCreatedByEmpty(): bool
    {
        return empty($this->getCreatedById())
            && empty($this->getCreatedByName())
            && empty($this->getCreatedByEmail());
    }

    public function getCreatedBy()
    {
        $created_by = $this->getCreatedById() ? Users::findById($this->getCreatedById()) : null;

        if ($created_by instanceof User) {
            return $created_by;
        } elseif ($this->getCreatedByEmail()) {
            return new AnonymousUser($this->getCreatedByName(), $this->getCreatedByEmail());
        } else {
            return new AnonymousUser(null, 'unknown@example.com');
        }
    }

    /**
     * Set instance of user who created parent object.
     *
     * @param User|AuthenticatedUserInterface|IUser|null $created_by
     */
    public function setCreatedBy($created_by)
    {
        if ($created_by === null) {
            $this->setCreatedById(0);
            $this->setCreatedByName('');
            $this->setCreatedByEmail('');
        } elseif ($created_by instanceof User) {
            $this->setCreatedById($created_by->getId());
            $this->setCreatedByName($created_by->getDisplayName());
            $this->setCreatedByEmail($created_by->getEmail());
        } elseif ($created_by instanceof AnonymousUser) {
            $this->setCreatedById(0);
            $this->setCreatedByName($created_by->getName());
            $this->setCreatedByEmail($created_by->getEmail());
        }
    }

    /**
     * Return true if $user is author of this object.
     *
     * @return bool
     */
    public function isCreatedBy(IUser $user)
    {
        if ($this->getCreatedById()) {
            return $this->getCreatedById() == $user->getId();
        } else {
            return $this->getCreatedById() == 0 && $this->getCreatedByEmail() == $user->getEmail();
        }
    }

    // ---------------------------------------------------
    //  Expectatons
    // ---------------------------------------------------

    abstract protected function registerEventHandler(string $event, callable $handler): void;

    /**
     * Return ID of user who created this object.
     *
     * @return int
     */
    abstract public function getCreatedById();

    /**
     * Set ID of user who created this object.
     *
     * @param  int $value
     * @return int
     */
    abstract public function setCreatedById($value);

    /**
     * Return name of user who created this object.
     *
     * @return string
     */
    abstract public function getCreatedByName();

    /**
     * Set name of user who created this object.
     *
     * @param  string $value
     * @return string
     */
    abstract public function setCreatedByName($value);

    /**
     * Return email of user who created this object.
     *
     * @return string
     */
    abstract public function getCreatedByEmail();

    /**
     * Set email of user who created this object.
     *
     * @param  string $value
     * @return string
     */
    abstract public function setCreatedByEmail($value);
}
