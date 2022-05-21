<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

final class CTANotifications
{
    /**
     * @var bool
     */
    private $is_on_demand;

    public function __construct(
        $is_on_demand = null
    )
    {
        if ($is_on_demand === null) {
            $this->is_on_demand = AngieApplication::isOnDemand();
        }

        $this->is_on_demand = $is_on_demand;
    }

    public function loadNotification($type)
    {
        if ($type !== FillOnboardingSurveyNotification::class) {
            throw new InvalidArgumentException('Unknown CTA notification');
        }

        $memories = AngieApplication::memories()->getInstance();
        $setup_wizard = AngieApplication::setupWizard($this->is_on_demand);
        $stage = (new FillOnboardingSurveyNotificationStageResolver(
            $memories,
            DateTimeValue::now()->getTimestamp(),
            $setup_wizard->getGrantedAccessAt(),
            FillOnboardingSurveyNotification::FIRST_DELAY,
            FillOnboardingSurveyNotification::SECOND_DELAY
        ))->resolveStage();

        return new FillOnboardingSurveyNotification(
            $memories,
            $setup_wizard,
            AngieApplication::authentication()->getAuthenticatedUser(),
            $stage,
            $this->is_on_demand
        );
    }
}
