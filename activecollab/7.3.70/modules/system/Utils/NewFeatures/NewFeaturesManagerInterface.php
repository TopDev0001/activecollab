<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\NewFeatures;

use DateTimeValue;
use DateValue;
use User;

/**
 * Get the list of the new application features.
 *
 * @package ActiveCollab.modules.system
 * @subpackage model
 */
interface NewFeaturesManagerInterface
{
    /**
     * Get the list of new features for the user.
     *
     * @return NewFeatureAnnouncementInterface[]
     */
    public function get(User $user, DateValue $date = null, bool $record_last_visit = false): array;

    public function getJson(User $user, DateValue $date = null, bool $record_last_visit = false): array;

    public function countUnseen(User $user, DateValue $date = null): int;

    public function getLastVisit(User $user): ?DateTimeValue;

    public function recordLastVisit(User $user, DateTimeValue $timestamp = null): DateTimeValue;
}
