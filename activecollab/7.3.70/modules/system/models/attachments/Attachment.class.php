<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Attachment class.
 *
 * @package activeCollab.modules.system
 * @subpackage models
 */
abstract class Attachment extends FwAttachment
{
    /**
     * Prepare for JSON serialization.
     */
    public function jsonSerialize(): array
    {
        $result = parent::jsonSerialize();

        $result['project_id'] = $this->getProjectId();
        $result['is_hidden_from_clients'] = $this->getIsHiddenFromClients();

        return $result;
    }

    public function canEdit(User $user): bool
    {
        return $user->isPowerUser() ? true : parent::canEdit($user);
    }

    /**
     * Set parent instance.
     *
     * @param  ApplicationObject|null $parent
     * @param  bool                   $save
     * @return ApplicationObject
     */
    public function setParent($parent, $save = false)
    {
        $this->cacheParentData($parent);

        return parent::setParent($parent, $save);
    }

    /**
     * Cache parent data on based on parent.
     *
     * @param ApplicationObject|IProjectElement|ProjectTemplateElement|null $parent
     */
    private function cacheParentData($parent)
    {
        if ($parent instanceof IProjectElement) {
            $this->setProjectId($parent->getProjectId());
        } else {
            $this->setProjectId(0);
        }

        if ($parent instanceof IHiddenFromClients) {
            $this->setIsHiddenFromClients($parent->getIsHiddenFromClients());
        } else {
            $this->setIsHiddenFromClients(false);
        }
    }

    public function getLocation()
    {
        if ($this->getFieldValue('location')) {
            return $this->getFieldValue('location');
        }

        if ($this->getAdditionalProperty('location')) {
            return $this->getAdditionalProperty('location');
        }

        return null;
    }

    public function getMd5()
    {
        if ($this->getFieldValue('md5')) {
            return $this->getFieldValue('md5');
        }

        if ($this->getAdditionalProperty('md5')) {
            return $this->getAdditionalProperty('md5');
        }

        return null;
    }

    /**
     * Save attachment to database.
     */
    public function save()
    {
        if (!$this->getProjectId()) {
            $parent = $this->getParent();

            if ($parent instanceof IProjectElement || $parent instanceof ProjectTemplateElement) {
                $this->cacheParentData($parent);
            } elseif ($parent instanceof Comment) {
                $comment_parent = $parent->getParent();

                if ($comment_parent instanceof IProjectElement) {
                    $this->cacheParentData($comment_parent);
                }
            }
        }

        parent::save();
    }
}
