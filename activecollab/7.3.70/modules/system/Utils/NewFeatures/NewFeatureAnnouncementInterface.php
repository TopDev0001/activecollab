<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\NewFeatures;

use ActiveCollab\Module\System\Utils\NewFeatures\CallToAction\CallToActionInterface;
use DateTimeValue;
use DateValue;
use JsonSerializable;
use User;

interface NewFeatureAnnouncementInterface extends JsonSerializable
{
    const CHANNEL_CLOUD = 'cloud';
    const CHANNEL_SELF_HOSTED = 'self-hosted';

    const CHANNELS = [
        self::CHANNEL_CLOUD,
        self::CHANNEL_SELF_HOSTED,
    ];

    public function getCallToAction(): ?CallToActionInterface;
    public function isVisibleToUser(User $user): bool;
    public function isVisibleInChannel(string $channel): bool;
    public function isVisibleOnDate(DateValue $date): bool;
    public function isSeen(?DateTimeValue $timestamp): bool;
}
