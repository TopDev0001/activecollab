<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class SystemNotification extends BaseSystemNotification
{
    /**
     * Dismiss this system notification.
     */
    public function dismiss()
    {
        if (!$this->isPermanent()) {
            $this->setIsDismissed(true);
            $this->save();
        }

        return $this;
    }

    /**
     * Return is permanent.
     *
     * @return mixed
     */
    abstract public function isPermanent();

    /**
     * Return true if this notification action should be handled on frontend.
     *
     * @return mixed
     */
    public function isHandledInternally()
    {
        return false;
    }

    /**
     * Return true if user can dismiss notification.
     *
     * @return bool
     */
    public function canDismiss(User $user)
    {
        return !$this->isPermanent() && $user->getId() == $this->getRecipientId();
    }

    /**
     * Return array or property => value pairs that describes this object.
     */
    public function jsonSerialize(): array
    {
        $result = parent::jsonSerialize();

        $result['title'] = $this->getTitle();
        $result['body'] = $this->getBody();
        $result['action'] = $this->getAction();
        $result['url'] = $this->getUrl();
        $result['secondary_action'] = $this->getSecondaryAction();
        $result['secondary_url'] = $this->getSecondaryUrl();
        $result['permanent'] = $this->isPermanent();
        $result['is_handled_internally'] = $this->isHandledInternally();

        return $result;
    }

    /**
     * Return notification title.
     *
     * @return mixed
     */
    abstract public function getTitle();

    /**
     * Return notification body.
     *
     * @return mixed
     */
    abstract public function getBody();

    /**
     * Return notification action.
     *
     * @return mixed
     */
    abstract public function getAction();

    /**
     * Return secondary action.
     */
    public function getSecondaryAction(): ?string {
        return null;
    }

    /**
     * Return notification url.
     *
     * @return mixed
     */
    abstract public function getUrl();

    /**
     * Return secondary action.
     */
    public function getSecondaryUrl(): ?string {
        return null;
    }
}
