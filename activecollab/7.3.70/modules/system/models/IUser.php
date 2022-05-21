<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Foundation\Urls\PermalinkInterface;
use ActiveCollab\User\UserInterface;

interface IUser extends UserInterface, PermalinkInterface
{
    /**
     * Return user ID.
     *
     * @return int
     */
    public function getId();

    /**
     * Return name of this user.
     *
     * @return string
     */
    public function getName();

    public function getEmail(): string;
    public function getDisplayName(bool $short = false): string;

    /**
     * Return first name of this user.
     *
     * @return string
     */
    public function getFirstName(): ?string;

    /**
     * Return language instance.
     *
     * In case user is using default language, system will return NULL
     *
     * @return Language
     */
    public function getLanguage();

    public function getDateFormat(): string;
    public function getTimeFormat(): string;
    public function getDateTimeFormat(): string;

    /**
     * Returns true if this user has access to reports section.
     *
     * @return bool
     */
    public function canUseReports();

    /**
     * Returns true if this particular account is active.
     *
     * @return bool
     */
    public function isActive();

    /**
     * Return true if this instance is member.
     *
     * @param  bool $explicit
     * @return bool
     */
    public function isMember($explicit = false);

    /**
     * Returns true if this user has final management permissions.
     *
     * @param  bool $explicit
     * @return bool
     */
    public function isFinancialManager($explicit = false);

    /**
     * Returns true only if this person is application owner.
     *
     * @return bool
     */
    public function isOwner();

    /**
     * Return true if this instance is client.
     *
     * @param  bool $explicit
     * @return bool
     */
    public function isClient($explicit = false);

    /**
     * Return user avatar URL.
     *
     * @param  string|int $size
     * @return string
     */
    public function getAvatarUrl($size = '--SIZE--');

    public function isChargeable(): bool;
}
