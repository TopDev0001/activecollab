<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

interface CTANotificationInterface
{
    public function getTitle();

    public function getBody();

    public function getAction();

    public function shouldShow();

    public function dismiss();
}
