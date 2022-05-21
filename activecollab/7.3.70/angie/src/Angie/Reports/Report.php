<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Reports;

use User;

interface Report
{
    const DONT_GROUP = 'dont';

    public function canRun(User $user): bool;
    public function run(User $user, array $additional = null): ?array;
    public function export(User $user, array $additional = null): ?string;

    /**
     * Set object attributes / properties. This function will take hash and set
     * value of all fields that she finds in the hash.
     *
     * @param array $attributes
     */
    public function setAttributes($attributes);

    public function canBeGroupedBy(): array;
    public function getGroupingMaxLevel(): int;
    public function isGrouped(): bool;

    /**
     * Return array of properties that this report should be grouped by.
     *
     * @return array
     */
    public function getGroupBy();

    /**
     * Set group by.
     */
    public function setGroupBy();

    /**
     * Reset group by settings.
     */
    public function ungroup();
}
