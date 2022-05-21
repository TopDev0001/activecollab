<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Utils\AccountConfigReader;

use AccountStatusInterface;
use ActiveCollab\Module\OnDemand\Models\Pricing\AccountPlanInterface;
use ActiveCollab\Module\OnDemand\Models\Pricing\PricingModelInterface;
use ActiveCollab\ShepherdAccountConfig\Utils\ShepherdAccountConfigInterface;
use DateValue;

class DatabaseConfigReader implements AccountConfigReaderInterface
{
    /**
     * @var ShepherdAccountConfigInterface
     */
    private $shepherd_account_config;

    /**
     * @var int
     */
    private $on_demand_instance_id;

    /**
     * @var string
     */
    private $plan;

    /**
     * @var float
     */
    private $plan_price;

    /**
     * @var string
     */
    private $billing_period;

    /**
     * @var bool
     */
    private $is_activated;

    /**
     * @var string
     */
    private $status;

    /**
     * @var DateValue
     */
    private $status_expires_on;

    /**
     * @var DateValue
     */
    private $reference_billing_date;

    /**
     * @var DateValue
     */
    private $next_billing_date;

    /**
     * @var int
     */
    private $max_members;

    /**
     * @var int
     */
    private $max_projects;

    /**
     * @var int
     */
    private $max_disk_space;

    /**
     * @var bool
     */
    private $is_paid;

    /**
     * @var bool
     */
    private $is_non_profit;

    /**
     * @var string
     */
    private $pricing_model;

    /**
     * @var float
     */
    private $mrr_value;

    /**
     * @var int
     */
    private $chargeable_users_count;

    /**
     * @var
     */
    private $discount;

    /**
     * @var
     */
    private $mail_route;

    public function __construct(
        ShepherdAccountConfigInterface $shepherd_account_config,
        int $on_demand_instance_id
    ) {
        $this->shepherd_account_config = $shepherd_account_config;
        $this->on_demand_instance_id = $on_demand_instance_id;

        $this->init();
    }

    private function init()
    {
        $data = $this->shepherd_account_config->getShepherdAccountConfig($this->on_demand_instance_id);

        $this->plan = (string) ($data['plan'] ?? AccountPlanInterface::LEGACY_PLAN_EXTRA_LARGE);
        $this->plan_price = (float) ($data['plan_price'] ?? 0.0);
        $this->billing_period = (string) ($data['billing_period'] ?? AccountPlanInterface::BILLING_PERIOD_MONTHLY);
        $this->is_activated = (bool) ($data['is_activated'] ?? false);
        $this->status = (string) ($data['status'] ?? AccountStatusInterface::STATUS_TRIAL);

        if (empty($data['status_expires_on'])) {
            $this->status_expires_on = new DateValue();
        } else {
            $this->status_expires_on = new DateValue($data['status_expires_on']);
        }

        $this->reference_billing_date = !empty($data['reference_billing_date']) ? new DateValue($data['reference_billing_date']) : null;
        $this->next_billing_date = !empty($data['next_billing_date']) ? new DateValue($data['next_billing_date']) : null;
        $this->is_paid = (bool) ($data['is_paid'] ?? false);
        $this->is_non_profit = (bool) ($data['is_non_profit'] ?? false);
        $this->max_members = (int) ($data['max_members'] ?? 0);
        $this->max_projects = (int) ($data['max_projects'] ?? 0);
        $this->max_disk_space = (int) ($data['max_disk_space'] ?? 0);
        $this->pricing_model = (string) ($data['pricing_model'] ?? PricingModelInterface::PRICING_MODEL_PLANS_2013);
        $this->discount = (string) ($data['discount'] ?? null);
        $this->mrr_value = (float) ($data['mrr_value'] ?? 0.00);
        $this->chargeable_users_count = (int) ($data['chargeable_users_count'] ?? 0);
        $this->mail_route = (string) ($data['mail_route'] ?? 'default');
    }

    public function getPlan(): string
    {
        return $this->plan;
    }

    public function getBillingPeriod(): string
    {
        return $this->billing_period;
    }

    public function getPlanPrice(): float
    {
        return $this->plan_price;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getStatusExpiresOn(): DateValue
    {
        return $this->status_expires_on;
    }

    /**
     * @return DateValue
     */
    public function getReferenceBillingDate(): ?DateValue
    {
        return $this->reference_billing_date;
    }

    /**
     * @return DateValue
     */
    public function getNextBillingDate(): ?DateValue
    {
        return $this->next_billing_date;
    }

    public function getMaxMembers(): int
    {
        return $this->max_members;
    }

    public function getMaxProjects(): int
    {
        return $this->max_projects;
    }

    public function getMaxDiskSpace(): int
    {
        return $this->max_disk_space;
    }

    public function isActivated(): bool
    {
        return $this->is_activated;
    }

    public function isPaid(): bool
    {
        return $this->is_paid;
    }

    public function isNonProfit(): bool
    {
        return $this->is_non_profit;
    }

    public function getPricingModel(): string
    {
        return $this->pricing_model;
    }

    public function getDiscount(): string
    {
        return $this->discount;
    }

    public function getMrrValue(): float
    {
        return $this->mrr_value;
    }

    public function getChargeableUsersCountValue(): int
    {
        return $this->chargeable_users_count;
    }

    public function getMailRoute(): string
    {
        return $this->mail_route;
    }
}
