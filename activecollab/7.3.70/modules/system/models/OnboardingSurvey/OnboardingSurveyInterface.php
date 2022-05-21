<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\User\UserInterface;

/**
 * Interface OnboardingSurveyInterface.
 */
interface OnboardingSurveyInterface
{
    const LEAD_SIZE_1_TO_5 = '1-5';
    const LEAD_SIZE_6_TO_15 = '6-15';
    const LEAD_SIZE_16_TO_30 = '16-30';
    const LEAD_SIZE_31_TO_60 = '31-60';
    const LEAD_SIZE_61_PLUS = '61+';
    const LEAD_SIZES = [
        self::LEAD_SIZE_1_TO_5,
        self::LEAD_SIZE_6_TO_15,
        self::LEAD_SIZE_16_TO_30,
        self::LEAD_SIZE_31_TO_60,
        self::LEAD_SIZE_61_PLUS,
    ];

    const LEAD_CHAMPION_ROLE_MANGER_EXECUTIVE = 'Manager/Executive';
    const LEAD_CHAMPION_ROLE_BUSINESS_OWNER = 'Business Owner';
    const LEAD_CHAMPION_ROLE_PROJECT_MANAGER = 'Project Manager';
    const LEAD_CHAMPION_ROLE_MARKETER = 'Marketer';
    const LEAD_CHAMPION_ROLE_DEVELOPER = 'Developer';
    const LEAD_CHAMPION_ROLE_DESIGNER = 'Designer';
    const LEAD_CHAMPION_ROLE_PRODUCT_MANAGER = 'Product Manager';
    const LEAD_CHAMPION_ROLE_HR_MANAGER = 'HR Manager';
    const LEAD_CHAMPION_ROLE_SALES_MANAGER = 'Sales Manager';
    const LEAD_CHAMPION_ROLE_CUSTOMER_SUPPORT = 'Customer Support';
    const LEAD_CHAMPION_ROLE_STUDENT = 'Student';
    const LEAD_CHAMPION_ROLE_OTHER = 'Other';
    const LEAD_CHAMPION_ROLES = [
        self::LEAD_CHAMPION_ROLE_MANGER_EXECUTIVE,
        self::LEAD_CHAMPION_ROLE_BUSINESS_OWNER,
        self::LEAD_CHAMPION_ROLE_PROJECT_MANAGER,
        self::LEAD_CHAMPION_ROLE_MARKETER,
        self::LEAD_CHAMPION_ROLE_DEVELOPER,
        self::LEAD_CHAMPION_ROLE_DESIGNER,
        self::LEAD_CHAMPION_ROLE_PRODUCT_MANAGER,
        self::LEAD_CHAMPION_ROLE_HR_MANAGER,
        self::LEAD_CHAMPION_ROLE_SALES_MANAGER,
        self::LEAD_CHAMPION_ROLE_CUSTOMER_SUPPORT,
        self::LEAD_CHAMPION_ROLE_STUDENT,
        self::LEAD_CHAMPION_ROLE_OTHER,
    ];

    const LEAD_INDUSTRY_IT_COMPANY = 'IT company';
    const LEAD_INDUSTRY_MARKETING_AGENCY = 'Marketing Agency';
    const LEAD_INDUSTRY_DIGITAL_AGENCY = 'Digital Agency';
    const LEAD_INDUSTRY_CREATIVE_STUDIO = 'Creative Studio';
    const LEAD_INDUSTRY_SEO_AGENCY = 'SEO Agency';
    const LEAD_INDUSTRY_INDEPENDENT_CONSULTANT_FREELANCER = 'Independent consultant/freelancer';
    const LEAD_INDUSTRY_STARTUP = 'StartUp';
    const LEAD_INDUSTRY_EDUCATIONAL_OR_SCIENCE_INSTITUTION = 'Educational or Science Institution';
    const LEAD_INDUSTRY_NON_PROFIT = 'NonProfit';
    const LEAD_INDUSTRY_OTHER = 'Other';
    const LEAD_INDUSTRIES = [
        self::LEAD_INDUSTRY_IT_COMPANY,
        self::LEAD_INDUSTRY_MARKETING_AGENCY,
        self::LEAD_INDUSTRY_DIGITAL_AGENCY,
        self::LEAD_INDUSTRY_CREATIVE_STUDIO,
        self::LEAD_INDUSTRY_SEO_AGENCY,
        self::LEAD_INDUSTRY_INDEPENDENT_CONSULTANT_FREELANCER,
        self::LEAD_INDUSTRY_STARTUP,
        self::LEAD_INDUSTRY_EDUCATIONAL_OR_SCIENCE_INSTITUTION,
        self::LEAD_INDUSTRY_NON_PROFIT,
        self::LEAD_INDUSTRY_OTHER,
    ];

    /**
     * Return true if onboarding survey should be shown to the user.
     *
     * @return bool
     */
    public function shouldShow(User $user = null);

    /**
     * @return int|null
     */
    public function getLeadSurveySubmittedOn();

    /**
     * @param  int              $lead_survey_submitted_on
     * @return OnboardingSurvey
     */
    public function setLeadSurveySubmittedOn($lead_survey_submitted_on);

    /**
     * @return bool
     */
    public function isLeadSurveySubmitted();

    /**
     * @return bool
     */
    public function isLeadSurveySkipped();

    /**
     * @param  bool             $lead_survey_skipped
     * @return OnboardingSurvey
     */
    public function setLeadSurveySkipped($lead_survey_skipped);

    /**
     * @return string
     */
    public function getLeadName();

    /**
     * @param  string           $lead_name
     * @return OnboardingSurvey
     */
    public function setLeadName($lead_name);

    /**
     * @return string
     */
    public function getLeadChampionRole();

    /**
     * @param  string           $lead_champion_role
     * @return OnboardingSurvey
     */
    public function setLeadChampionRole($lead_champion_role);

    /**
     * @param  int              $lead_champion_id
     * @return OnboardingSurvey
     */
    public function setLeadChampionId($lead_champion_id);

    /**
     * @return string
     */
    public function getLeadSize();

    /**
     * @param  string           $lead_size
     * @return OnboardingSurvey
     */
    public function setLeadSize($lead_size);

    /**
     * @return string
     */
    public function getLeadIndustry();

    /**
     * @param  string           $lead_industry
     * @return OnboardingSurvey
     */
    public function setLeadIndustry($lead_industry);

    /**
     * @return int
     */
    public function getLeadChampionId();

    /**
     * @return int
     */
    public function getLeadSurveySentToShepherdOn();

    /**
     * @param  int              $lead_survey_sent_to_shepherd_on
     * @return OnboardingSurvey
     */
    public function setLeadSurveySentToShepherdOn($lead_survey_sent_to_shepherd_on);

    /**
     * @return mixed
     */
    public function setShowedFirstTime(DateTimeValue $time);

    /**
     * @return mixed
     */
    public function getShowedFirstTime();

    /**
     * @param $counter
     * @return mixed
     */
    public function setHowManyTimesShowed($counter = 1);

    /**
     * @return mixed
     */
    public function getHowManyTimesShowed();

    /**
     * Persist changes.
     */
    public function save();

    /**
     * Rehydrate properties.
     */
    public function reloadData();

    /**
     * @return mixed
     */
    public function getOnboardUserData(UserInterface $user);

    /**
     * @param  null   $current_timestamp
     * @param  string $recipinet_mail
     * @return mixed
     */
    public function notifyCSMTeamAboutSurveySubmmited($recipinet_mail, $current_timestamp);
}
