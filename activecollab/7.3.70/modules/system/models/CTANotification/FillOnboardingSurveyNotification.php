<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Memories\Memories;

final class FillOnboardingSurveyNotification implements FillOnboardingSurveyNotificationInterface, JsonSerializable
{
    const MEMORIES_PREFIX = 'fill_onboarding_survey_cta_';
    const FIRST_DELAY = 1 * 24 * 60 * 60; // 1 day in seconds
    const SECOND_DELAY = 2 * 24 * 60 * 60; // 2 days in seconds

    /**
     * @var Memories
     */
    private $memories;

    /**
     * @var bool
     */
    private $is_on_demand;

    /**
     * @var SetupWizardInterface
     */
    private $setup_wizard;

    /**
     * @var User
     */
    private $user;

    /**
     * @var int
     */
    private $stage;

    public function __construct(
        Memories $memories,
        SetupWizardInterface $setup_wizard,
        User $user,
        $stage = 1,
        $is_on_demand = false
    )
    {
        $this->memories = $memories;
        $this->setup_wizard = $setup_wizard;
        $this->user = $user;
        $this->stage = $stage;
        $this->is_on_demand = $is_on_demand;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return lang('Hey!');
    }

    /**
     * @return string*
     */
    public function getBody()
    {
        return lang('We would like to improve your ActiveCollab experience. Let us hijack your attention for just 30 seconds, we have a few short questions.');
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return lang('Go');
    }

    public function shouldShow()
    {
      if (!($this->is_on_demand || $this->setup_wizard->shouldShowOnboardingSurvey($this->user))) {
            return false;
        }

        if ($this->stage === FillOnboardingSurveyNotificationStageResolver::STAGE_1) {
            return false;
        } elseif ($this->stage === FillOnboardingSurveyNotificationStageResolver::STAGE_2) {
            return $this->isVisible() && !$this->isDismissed();
        } elseif ($this->stage === FillOnboardingSurveyNotificationStageResolver::STAGE_3) {
            return $this->isVisible() && !$this->isDismissed();
        } else {
            return false;
        }
    }

    public function dismiss()
    {
        $this->setMemory('visible', 0);
        $this->setMemory('dismissed', 1);

        return true;
    }

    public function isVisible()
    {
        return (bool) $this->getMemory('visible', false);
    }

    public function isDismissed()
    {
        return (bool) $this->getMemory('dismissed', false);
    }

    public function jsonSerialize(): array
    {
        return [
            'title' => $this->getTitle(),
            'body' => $this->getBody(),
            'action' => $this->getAction(),
            'should_show' => $this->shouldShow(),
            'stage' => $this->stage,
        ];
    }

    /**
     * @param  string $key
     * @param  null   $if_not_found
     * @param  bool   $use_cache
     * @return mixed
     */
    private function getMemory($key, $if_not_found = null, $use_cache = true)
    {
        return $this->memories->get(static::MEMORIES_PREFIX . $key, $if_not_found, $use_cache);
    }

    /**
     * @param        $key
     * @param  null  $value
     * @param  bool  $bulk
     * @return array
     */
    private function setMemory($key, $value = null, $bulk = false)
    {
        return $this->memories->set(static::MEMORIES_PREFIX . $key, $value, $bulk);
    }
}
