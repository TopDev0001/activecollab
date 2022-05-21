<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Wires\AvatarProxy;
use ActiveCollab\User\IdentifiedVisitor;
use ActiveCollab\User\UserInterface\ImplementationUsingFullName;

/**
 * Anonymous user class.
 *
 * @package AtiveCollab.modules.system
 * @subpackage models
 */
class AnonymousUser extends IdentifiedVisitor implements IUser
{
    use ImplementationUsingFullName;

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'class' => get_class($this),
            'first_name' => $this->getFirstName(),
            'last_name' => $this->getLastName(),
            'display_name' => $this->getDisplayName(),
            'short_display_name' => $this->getDisplayName(true),
            'email' => $this->getEmail(),
        ];
    }

    public function getDisplayName(bool $short = false): string
    {
        return Users::getUserDisplayName(
            [
                'full_name' => $this->getFullName(),
                'email' => $this->getEmail(),
            ],
            $short
        );
    }

    public function getViewUrl(): string
    {
        return 'mailto:' . $this->getEmail();
    }

    /**
     * Cached langauge instance.
     *
     * @var Language
     */
    private $language = false;

    /**
     * Return user's language.
     *
     * @return Language
     */
    public function getLanguage()
    {
        if ($this->language === false) {
            $this->language = Languages::findDefault();
        }

        return $this->language;
    }

    public function getDateFormat(): string
    {
        return FORMAT_DATE;
    }

    public function getTimeFormat(): string
    {
        return FORMAT_TIME;
    }

    public function getDateTimeFormat(): string
    {
        return FORMAT_DATETIME;
    }

    /**
     * Returns true if this user has access to reports section.
     *
     * @return bool
     */
    public function canUseReports()
    {
        return false;
    }

    /**
     * Returns true if this account is active.
     *
     * @return bool
     */
    public function isActive()
    {
        return true;
    }

    /**
     * Return true if this instance is a member.
     *
     * @param  bool $explicit
     * @return bool
     */
    public function isMember($explicit = false)
    {
        return false;
    }

    /**
     * Returns true if this user is member of owner company.
     *
     * @return bool
     */
    public function isOwner()
    {
        return false;
    }

    public function isClient($explicit = false)
    {
        return false;
    }

    /**
     * Returns true if this user has global project management permissions.
     *
     * @return bool
     */
    public function isProjectManager()
    {
        return false;
    }

    /**
     * Returns true if this user has final management permissions.
     *
     * @param  bool $explicit
     * @return bool
     */
    public function isFinancialManager($explicit = false)
    {
        return false;
    }

    // ---------------------------------------------------
    //  Getters and setters
    // ---------------------------------------------------

    public function getUrlPath(): string
    {
        return '/';
    }

    /**
     * Get value of name.
     *
     * @return string
     * @deprecated Use getFullName() or getDispayName() instead
     */
    public function getName()
    {
        return $this->getFullName();
    }

    /**
     * Return user avatar URL.
     *
     * @param  string|int $size
     * @return string
     */
    public function getAvatarUrl($size = '--SIZE--')
    {
        return AngieApplication::getProxyUrl(
            AvatarProxy::class,
            EnvironmentFramework::INJECT_INTO,
            [
                'user_id' => 0,
                'size' => $size,
            ]
        );
    }

    public function isChargeable(): bool
    {
        return false;
    }
}
