<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\CurrentTimestamp\CurrentTimestampInterface;
use ActiveCollab\Memories\Memories;
use ActiveCollab\Module\System\Utils\OwnerCompanyResolver\OwnerCompanyResolverInterface;
use ActiveCollab\User\UserInterface;

final class OnboardingSurvey implements OnboardingSurveyInterface, JsonSerializable
{
    /**
     * @var Memories
     */
    private $memories;

    /**
     * @var int
     */
    private $first_owner_id;

    /**
     * @var bool
     */
    private $is_on_demand;

    /**
     * @var CurrentTimestampInterface
     */
    private $current_timestamp;

    /**
     * @var bool
     */
    private $is_new = true;

    /**
     * @var int
     */
    private $lead_survey_submitted_on;

    /**
     * @var bool
     */
    private $lead_survey_skipped;

    /**
     * @var string
     */
    private $lead_name;

    /**
     * @var string
     */
    private $lead_champion_role;

    /**
     * @var string
     */
    private $lead_size;

    /**
     * @var string
     */
    private $lead_industry;

    /**
     * @var int
     */
    private $lead_champion_id;

    /**
     * @var int
     */
    private $lead_survey_sent_to_shepherd_on;

    public function __construct(
        Memories $memories,
        $first_owner_id,
        $is_on_demand,
        CurrentTimestampInterface $current_timestamp
    )
    {
        $this->memories = $memories;
        $this->first_owner_id = $first_owner_id;
        $this->is_on_demand = $is_on_demand;
        $this->current_timestamp = $current_timestamp;

        $this->reloadData();
    }

    public function shouldShow(User $user = null)
    {
        if (!$this->is_on_demand) {
            return false;
        }

        if (!$user instanceof User) {
            return false;
        }

        if ($user->getId() !== $this->first_owner_id) {
            return false;
        }

        if ($this->isLeadSurveySkipped() || $this->isLeadSurveySubmitted()) {
            return false;
        }

        return true;
    }

    /**
     * @return int|null
     */
    public function getLeadSurveySubmittedOn()
    {
        return $this->getData('lead_survey_submitted_on');
    }

    /**
     * @param  int              $lead_survey_submitted_on
     * @return OnboardingSurvey
     */
    public function setLeadSurveySubmittedOn($lead_survey_submitted_on)
    {
        $this->lead_survey_submitted_on = $lead_survey_submitted_on;

        return $this;
    }

    public function isLeadSurveySubmitted()
    {
        return !empty($this->getLeadSurveySubmittedOn());
    }

    /**
     * @return bool
     */
    public function isLeadSurveySkipped()
    {
        return (bool) $this->getData('lead_survey_skipped', false);
    }

    /**
     * @param  bool             $lead_survey_skipped
     * @return OnboardingSurvey
     */
    public function setLeadSurveySkipped($lead_survey_skipped)
    {
        $this->lead_survey_skipped = $lead_survey_skipped;

        return $this;
    }

    /**
     * @return string
     */
    public function getLeadName()
    {
        return $this->getData('lead_name');
    }

    /**
     * @param  string           $lead_name
     * @return OnboardingSurvey
     */
    public function setLeadName($lead_name)
    {
      $this->lead_name = $lead_name;

        return $this;
    }

    /**
     * @return string
     */
    public function getLeadChampionRole()
    {
        return $this->getData('lead_champion_role');
    }

    /**
     * @param  string           $lead_champion_role
     * @return OnboardingSurvey
     */
    public function setLeadChampionRole($lead_champion_role)
    {
        if (!in_array($lead_champion_role, static::LEAD_CHAMPION_ROLES)) {
            throw new LogicException('Illegal choice: role in the team must be one of the listed ones');
        }

        $this->lead_champion_role = $lead_champion_role;

        return $this;
    }

    /**
     * @param  int              $lead_champion_id
     * @return OnboardingSurvey
     */
    public function setLeadChampionId($lead_champion_id)
    {
        $this->lead_champion_id = $lead_champion_id;

        return $this;
    }

    /**
     * @return string
     */
    public function getLeadSize()
    {
        return $this->getData('lead_size');
    }

    /**
     * @param  string           $lead_size
     * @return OnboardingSurvey
     */
    public function setLeadSize($lead_size)
    {
        if (!in_array($lead_size, static::LEAD_SIZES)) {
            throw new LogicException('Illegal choice: team size must be one of the listed ones');
        }

        $this->lead_size = $lead_size;

        return $this;
    }

    /**
     * @return string
     */
    public function getLeadIndustry()
    {
        return $this->getData('lead_industry');
    }

    /**
     * @param  string           $lead_industry
     * @return OnboardingSurvey
     */
    public function setLeadIndustry($lead_industry)
    {
        if (!in_array($lead_industry, static::LEAD_INDUSTRIES)) {
            throw new LogicException('Illegal choice: team industry must be one of the listed ones');
        }

        $this->lead_industry = $lead_industry;

        return $this;
    }

    /**
     * @return int
     */
    public function getLeadChampionId()
    {
        return $this->getData('lead_champion_id');
    }

    /**
     * @return int
     */
    public function getLeadSurveySentToShepherdOn()
    {
        return $this->getData('lead_survey_sent_to_shepherd_on');
    }

    /**
     * @param  int              $lead_survey_sent_to_shepherd_on
     * @return OnboardingSurvey
     */
    public function setLeadSurveySentToShepherdOn($lead_survey_sent_to_shepherd_on)
    {
        $this->lead_survey_sent_to_shepherd_on = $lead_survey_sent_to_shepherd_on;

        return $this;
    }

    /**
     * @return $this
     */
    public function setShowedFirstTime(DateTimeValue $time)
    {
        if (empty($this->getShowedFirstTime())) {
            $this->memories->set('showed_first_time', $time->getTimestamp());
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getShowedFirstTime()
    {
        return $this->memories->get('showed_first_time', false);
    }

    /**
     * @param  int   $counter
     * @return $this
     */
    public function setHowManyTimesShowed($counter = 1)
    {
        $this->memories->set('how_many_times_showed', $this->getHowManyTimesShowed() + $counter);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getHowManyTimesShowed()
    {
        return $this->memories->get('how_many_times_showed', 0);
    }

    /**
     * Persist changes.
     */
    public function save()
    {
        $is_skipped = $this->getData('lead_survey_skipped', false);
        $submitted_on = $this->getData('lead_survey_submitted_on');

        if (!$is_skipped) {
            $submitted_on = $this->current_timestamp->getCurrentTimestamp();
        }

        if ($is_skipped && $submitted_on) {
            throw new LogicException("Onboarding survey can't be both submitted and skipped.");
        }

        $data = [
            'lead_size' => $this->getData('lead_size'),
            'lead_champion_role' => $this->getData('lead_champion_role'),
            'lead_champion_id' => $this->getData('lead_champion_id', $this->first_owner_id),
            'lead_name' => $this->getData('lead_name'),
            'lead_industry' => $this->getData('lead_industry'),
            'lead_survey_sent_to_shepherd_on' => $this->getData('lead_survey_sent_to_shepherd_on'),
            'lead_survey_submitted_on' => $submitted_on,
            'lead_survey_skipped' => $is_skipped,
        ];

        foreach ($data as $k => $v) {
            $this->memories->set($k, $v);
        }

        $this->is_new = false;

        $this->updateOwnerCompanyName($data['lead_name']);
        $this->reloadData();
    }

    /**
     * @param string $company_name
     */
    public function updateOwnerCompanyName($company_name)
    {
        $owner_company = AngieApplication::getContainer()
            ->get(OwnerCompanyResolverInterface::class)
                ->getCompany();

        if ($owner_company->getName() === Company::DEFAULT_COMPANY_NAME) {
            $owner_company->setName($company_name);
            $owner_company->save();
        }
    }

    /**
     * @param        $property
     * @param  null  $default
     * @return mixed
     */
    private function getData($property, $default = null)
    {
        if ($this->is_new) {
            if (!property_exists($this, $property)) {
                throw new LogicException("Invalid property '{$property}'");
            }

            return !empty($this->$property) ? $this->$property : $default;
        } else {
            return $this->memories->get($property, $default);
        }
    }

    /**
     * Rehydrate properties.
     */
    public function reloadData()
    {
        $properties = [
            'lead_size' => null,
            'lead_champion_role' => null,
            'lead_champion_id' => null,
            'lead_name' => null,
            'lead_industry' => null,
            'lead_survey_sent_to_shepherd_on' => null,
            'lead_survey_submitted_on' => null,
            'lead_survey_skipped' => false,
        ];

        foreach ($properties as $property_name => $if_not_found_default_value) {
            $this->$property_name = $this->memories->get($property_name, $if_not_found_default_value, false);
        }
    }

    /**
     * Returns an array of boolean values for checking if user's first and last name is
     * already set and if user company is set and different from default value.
     *
     * @return mixed
     */
    public function getOnboardUserData(UserInterface $user) {
        if (!empty($user->getFirstName()) && !empty($user->getLastName())) {
            $user_full_name_is_set = true;
        } else {
            $user_full_name_is_set = false;
        }

        if (empty($user->getCompany())){
            $user_company_is_set = false;
        } elseif ($user->getCompany()->getName() === Company::DEFAULT_COMPANY_NAME){
            $user_company_is_set = false;
        } else {
            $user_company_is_set = true;
        }

        return [
            'onboarding_full_name_is_set' => $user_full_name_is_set,
            'onboarding_company_is_set' => $user_company_is_set,
        ];
    }

    /**
     * @param  null   $current_timestamp
     * @param  string $recipinet_mail
     * @return $this
     */
    public function notifyCSMTeamAboutSurveySubmmited($recipinet_mail, $current_timestamp)
    {
        $date_for_creation_task = strtotime('+79 hours', AngieApplication::getAccountCreatedAt()->getTimestamp());

        if ($current_timestamp > $date_for_creation_task) {
            $account_id = AngieApplication::getAccountId();

            /** @var SurveySubmitedAfterCsmTaskCreatedNotification $notification */
            $notification = AngieApplication::notifications()
                ->notifyAbout('system/survey_submited_after_csm_task_created');

            $notification
                ->setSubject("Account #{$account_id} submitted survey.")
                ->setPayload($this->jsonSerialize())
                ->sendToUsers([new AnonymousUser('CSM Team', $recipinet_mail)]);
        }

        return $this;
    }

    public function jsonSerialize(): array
    {
        $user = Users::findFirstOwner();

        if (!empty($this->lead_name)) {
            $lead_name = $this->lead_name;
        } elseif ($company = $user->getCompany()) {
            $lead_name = $company->getName();
        } else {
            $lead_name = Company::DEFAULT_COMPANY_NAME;
        }

        return [
            'lead_size' => $this->lead_size,
            'lead_champion_role' => $this->lead_champion_role,
            'lead_name' => $lead_name,
            'owner_name' => $user->getFullName(),
            'lead_industry' => $this->lead_industry,
            'owner_email' => $user->getEmail(),
        ];
    }
}
