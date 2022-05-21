<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\HumanNameParser\Parser as HumanNameParser;
use ActiveCollab\Memories\Memories;
use ActiveCollab\Module\OnDemand\Models\Pricing\AccountPlanInterface;
use ActiveCollab\Module\OnDemand\Models\Pricing\Lifetime2021\Plan\AppSumoTierPlanInterface;
use ActiveCollab\Module\OnDemand\Utils\SubscribeToNewsletterService\SubscribeToNewsletterServiceInterface;
use ActiveCollab\ShepherdSDK\Api\Users\UsersApiInterface;
use ActiveCollab\User\UserInterface;

class SetupWizard implements SetupWizardInterface
{
    private $account_id;
    private $shepherd_users_api;
    private $shepherd_syncer;
    private $memories;
    private $first_owner;
    private $onboarding_survey;
    private $is_on_demand;
    private $human_name_parser;
    private $newsletter_service;

    public function __construct(
        int $account_id,
        ?UsersApiInterface $shepherd_users_api,
        ?ShepherdSyncerInterface $shepherd_syncer,
        Memories $memories,
        User $first_owner,
        OnboardingSurveyInterface $onboarding_survey,
        bool $is_on_demand,
        HumanNameParser $human_name_parser,
        ?SubscribeToNewsletterServiceInterface $newsletter_service
    ) {
        $this->account_id = $account_id;
        $this->shepherd_users_api = $shepherd_users_api;
        $this->shepherd_syncer = $shepherd_syncer;
        $this->memories = $memories;
        $this->first_owner = $first_owner;
        $this->onboarding_survey = $onboarding_survey;
        $this->is_on_demand = $is_on_demand;
        $this->human_name_parser = $human_name_parser;
        $this->newsletter_service = $newsletter_service;
    }

    /**
     * @return int
     */
    public function getWhenIsPasswordSetInWizard()
    {
        return $this->memories->get('password_set_at', null);
    }

    /**
     * @return $this
     */
    private function setGrantedAccessAt(DateTimeValue $datetime = null)
    {
        if (empty($datetime)) {
            $datetime = new DateTimeValue();
        }

        if (is_null($this->getGrantedAccessAt())) {
            $this->memories->set('granted_access_at', $datetime->getTimestamp());
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getGrantedAccessAt()
    {
        return $this->memories->get('granted_access_at', null);
    }

    /**
     * @param  bool  $value
     * @return $this
     */
    public function setOwnerHasRandomPassword($value = true)
    {
        if (is_null($this->getOwnerHasRandomPassword())) {
            $this->memories->set('owner_has_random_password', $value);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function getOwnerHasRandomPassword()
    {
        return $this->memories->get('owner_has_random_password', null);
    }

    /**
     * @return $this
     */
    public function setWhenIsPasswordSetInWizard(DateTimeValue $datetime = null)
    {
        if (empty($datetime)) {
            $datetime = new DateTimeValue();
        }

        $this->memories->set('password_set_at', $datetime->getTimestamp());

        return $this;
    }

    /**
     * @return $this
     */
    public function setWhenIsShownSetPasswordForm(DateTimeValue $datetime = null)
    {
        if (empty($datetime)) {
            $datetime = new DateTimeValue();
        }

        if (is_null($this->getWhenIsShownSetPasswordForm())) {
            $this->memories->set('password_set_wizard_shown_at', $datetime->getTimestamp());
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getWhenIsShownSetPasswordForm()
    {
        return $this->memories->get('password_set_wizard_shown_at', null);
    }

    /**
     * @return string
     */
    public function getNextWizardStep(User $user = null)
    {
        if ($this->shouldShowOnboardingSurvey($user)) {
            return self::WIZARD_STEP_ONBORDING_SURVEY;
        }

        return self::WIZARD_STEP_CONFORMATION;
    }

    /**
     * @param $user
     * @return bool
     */
    public function shouldShowOnboardingSurvey(UserInterface $user)
    {
        return $this->onboarding_survey->shouldShow($user);
    }

    /**
     * @return bool
     */
    public function shouldShowSetPassword(UserInterface $user)
    {
        if (!$this->is_on_demand) {
            return false;
        }

        if (!$user instanceof User) {
            return false;
        }

        if ($user->getId() !== $this->first_owner->getId()) {
            return false;
        }

        $is_password_generated = $this->isOwnerHasRandomPassword($user);
        $should_show = $is_password_generated && empty($this->getWhenIsPasswordSetInWizard()) && empty($this->getGrantedAccessAt());

        if ($should_show) {
            $this->setWhenIsShownSetPasswordForm();
            $this->setOwnerHasRandomPassword();
        } else {
            $this->setOwnerHasRandomPassword(false);
            $this->setGrantedAccessAt();
        }

        return $should_show;
    }

    public function setPasswordForUser(string $password, bool $should_subscribe_to_newsletter, UserInterface $user)
    {
        $errors = new ValidationErrors();

        if (empty($password)) {
            $errors->fieldValueIsRequired('password');
        }

        if ($errors->hasErrors()) {
            throw $errors;
        }

        $this->shepherd_syncer->changeUserPassword($user, null, $password, $password);

        if ($should_subscribe_to_newsletter) {
            $this->newsletter_service->subscribeFirstOwner($user);
        }

        $this->setWhenIsPasswordSetInWizard();
        $this->setGrantedAccessAt();
        $this->memories->set('owner_has_random_password', false);

        return $this;
    }

    /**
     * @param  string    $full_name
     * @return $this
     * @throws Exception
     */
    public function updateUserFirstAndLastName(User $user, $full_name)
    {
        if (empty($user->getFirstName()) || empty($user->getLastName())) {
            if (empty($full_name)) {
                throw new LogicException('First and Last name are required and cannot be empty');
            } else {
                $this->human_name_parser->setName($full_name);

                $user->setFirstName($this->human_name_parser->getFirst());
                $user->setLastName($this->human_name_parser->getLast());
                $user->save();

                $this->shepherd_syncer->changeUserProfile(
                    $user,
                    [
                        'email' => $user->getEmail(),
                        'first_name' => $user->getFirstName(),
                        'last_name' => $user->getLastName(),
                    ]
                );
            }
        }

        return $this;
    }

    private function isOwnerHasRandomPassword(User $user): bool
    {
        if (is_null($this->getOwnerHasRandomPassword())) {
            $check_user_password = $this->shepherd_users_api->checkUserPassword(
                $this->account_id,
                $user->getId()
            );

            return (bool) $check_user_password['is_password_generated'];
        }

        return true;
    }

    public function shouldShowFeaturesInfo(UserInterface $user, AccountPlanInterface $plan): bool
    {
        if (!$this->is_on_demand) {
            return false;
        }

        if (!$user instanceof User) {
            return false;
        }

        if ($user->getId() !== $this->first_owner->getId()) {
            return false;
        }

        if (is_null($this->getFeaturesInfoShownAt()) && $plan instanceof AppSumoTierPlanInterface) {
            $this->setFeaturesInfoShownAt();

            return true;
        }

        return false;
    }

    public function getFeaturesInfoShownAt()
    {
        return $this->memories->get('features_info_shown_at', null);
    }

    private function setFeaturesInfoShownAt(DateTimeValue $datetime = null)
    {
        if (empty($datetime)) {
            $datetime = new DateTimeValue();
        }

        if (is_null($this->getFeaturesInfoShownAt())) {
            $this->memories->set('features_info_shown_at', $datetime->getTimestamp());
        }

        return $this;
    }
}
