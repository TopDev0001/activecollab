<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

interface FillOnboardingSurveyNotificationInterface extends CTANotificationInterface
{
    public function isVisible();

    public function shouldShow();
}
