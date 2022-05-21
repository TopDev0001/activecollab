<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Utils\AccountConfigReader;

use DateValue;

interface AccountConfigReaderInterface
{
    public function getPlan(): string;

    public function getBillingPeriod(): string;

    public function getPlanPrice(): float;

    public function getStatus(): string;

    public function getStatusExpiresOn(): DateValue;

    /**
     * @return DateValue
     */
    public function getReferenceBillingDate(): ?DateValue;

    /**
     * @return DateValue
     */
    public function getNextBillingDate(): ?DateValue;

    public function getMaxMembers(): int;

    public function getMaxProjects(): int;

    public function getMaxDiskSpace(): int;

    public function isActivated(): bool;

    public function isPaid(): bool;

    /**
     * @deprecated
     */
    public function isNonProfit(): bool;

    public function getPricingModel(): string;

    public function getDiscount(): string;

    public function getMrrValue(): float;

    public function getChargeableUsersCountValue(): int;

    public function getMailRoute(): string;
}
