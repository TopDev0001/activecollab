<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Memories\Memories;

class FillOnboardingSurveyNotificationStageResolver
{
    const STAGE_1 = 1;
    const STAGE_2 = 2;
    const STAGE_3 = 3;

    /**
     * @var Memories
     */
    private $memories;

    /**
     * @var int
     */
    private $current_timestamp;

    /**
     * @var int
     */
    private $first_delay;

    /**
     * @var int
     */
    private $second_delay;

    /**
     * @var int
     */
    private $access_granted_at;

    /**
     * FillOnboardingSurveyNotificationStageResolver constructor.
     *
     * @param int $current_timestamp
     * @param int $access_granted_at
     * @param int $first_delay
     * @param int $second_delay
     */
    public function __construct(
        Memories $memories,
        $current_timestamp,
        $access_granted_at,
        $first_delay,
        $second_delay
    )
    {
        $this->memories = $memories;
        $this->current_timestamp = $current_timestamp;
        $this->access_granted_at = $access_granted_at;
        $this->first_delay = $first_delay;
        $this->second_delay = $second_delay;
    }

    public function resolveStage()
    {
        if (empty($this->access_granted_at)) {
            return self::STAGE_1;
        }

        $current_stage = $this->memories->get(FillOnboardingSurveyNotification::MEMORIES_PREFIX . 'stage', self::STAGE_1);

        if ($current_stage === self::STAGE_1) {
            if ($this->access_granted_at + $this->first_delay > $this->current_timestamp) {
                return self::STAGE_1;
            } elseif ($this->access_granted_at + $this->first_delay <= $this->current_timestamp && $this->access_granted_at + $this->second_delay > $this->current_timestamp) {
                $this->enterSecondStage();

                return self::STAGE_2;
            } else {
                $this->enterThirdStage();

                return self::STAGE_3;
            }
        } elseif ($current_stage === self::STAGE_2) {
            if ($this->access_granted_at + $this->first_delay < $this->current_timestamp && $this->access_granted_at + $this->second_delay <= $this->current_timestamp) {
                $this->enterThirdStage();

                return self::STAGE_3;
            } else {
                return self::STAGE_2;
            }
        } else {
            return self::STAGE_3;
        }
    }

    private function enterSecondStage()
    {
        $this->memories->set(FillOnboardingSurveyNotification::MEMORIES_PREFIX . 'stage', self::STAGE_2);
        $this->memories->set(FillOnboardingSurveyNotification::MEMORIES_PREFIX . 'visible', 1);
        $this->memories->set(FillOnboardingSurveyNotification::MEMORIES_PREFIX . 'dismissed', 0);
    }

    private function enterThirdStage()
    {
        $this->memories->set(FillOnboardingSurveyNotification::MEMORIES_PREFIX . 'stage', self::STAGE_3);
        $this->memories->set(FillOnboardingSurveyNotification::MEMORIES_PREFIX . 'visible', 1);
        $this->memories->set(FillOnboardingSurveyNotification::MEMORIES_PREFIX . 'dismissed', 0);
    }
}
