<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\App\RootUrl\RootUrlInterface;

/**
 * Shortcut class.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
final class Shortcut extends BaseShortcut
{
    const ICONS = [
        'insert_link',
        'project',
        'report',
        'checkbox-blank-toggler',
        'calendar',
        'note',
        'pencil',
        'dollar_document',
        'person',
        'labels',
        'settings',
    ];

    public function validate(ValidationErrors &$errors)
    {
        if (!$this->validatePresenceOf('name')) {
            $errors->fieldValueIsRequired('name');
        }

        if (!$this->validatePresenceOf('url')) {
            $errors->fieldValueIsRequired('url');
        } elseif (!filter_var($this->getUrl(), FILTER_VALIDATE_URL)) {
            $errors->addError(
                "Url '{$this->getUrl()}' isn't valid.",
                'url'
            );
        }

        if (!in_array($this->getIcon(), self::ICONS)) {
            $errors->addError(
                "Icon '{$this->getIcon()}' isn't valid.",
                'icon'
            );
        }

        parent::validate($errors);
    }

    public function canView(User $user): bool
    {
        return $user->getId() === $this->getCreatedById();
    }

    public function canDelete(User $user): bool
    {
        return $this->canView($user);
    }

    public function canEdit(User $user): bool
    {
        return $this->canView($user);
    }

    public function save()
    {
        if ($this->isNew() && !$this->getPosition()) {
            $this->setPosition(Shortcuts::getNextPositionForUser($this->getCreatedBy()));
        }

        if ($this->getUrl() && $this->getOldFieldValue('url') !== $this->getUrl()) {
            $this->setRelativeUrl(
                AngieApplication::getContainer()
                    ->get(RootUrlInterface::class)
                    ->getRelativeUrl($this->getUrl())
            );
        }

        return parent::save();
    }

    public function jsonSerialize(): array
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                'url' => $this->getUrl(),
                'relative_url' => $this->getRelativeUrl(),
                'position' => $this->getPosition(),
                'icon' => $this->getIcon(),
            ]
        );
    }
}
