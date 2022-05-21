<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\OnDemand\Models\Pricing\AccountPlanInterface;
use ActiveCollab\User\UserInterface;

interface SetupWizardInterface
{
    const WIZARD_STEP_ONBORDING_SURVEY = 'wizard_on_boarding';
    const WIZARD_STEP_CONFORMATION = 'wizard_confirmation';

    const WIZARD_STEPS = [
        self::WIZARD_STEP_ONBORDING_SURVEY,
        self::WIZARD_STEP_CONFORMATION,
    ];

    public function shouldShowOnboardingSurvey(UserInterface $user);

    public function shouldShowFeaturesInfo(UserInterface $user, AccountPlanInterface $plan): bool;

    public function shouldShowSetPassword(UserInterface $user);

    public function getNextWizardStep();

    public function setPasswordForUser(string $password, bool $should_subscribe_to_newsletter, UserInterface $user);

    public function getWhenIsPasswordSetInWizard();

    public function getWhenIsShownSetPasswordForm();

    public function setWhenIsShownSetPasswordForm(DateTimeValue $datetime);

    public function getOwnerHasRandomPassword();

    public function getGrantedAccessAt();
}
