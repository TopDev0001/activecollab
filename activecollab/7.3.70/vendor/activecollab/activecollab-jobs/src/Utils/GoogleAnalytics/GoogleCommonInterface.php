<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Utils\GoogleAnalytics;

interface GoogleCommonInterface
{
    const HIT_TYPE_EVENT = 'event';
    const HIT_TYPE_TRANSACTION = 'transaction';
    const HIT_TYPE_ITEM = 'item';
    const URL = 'https://www.google-analytics.com/collect';

    public function send(): void;

    const TRACKING_IDS = [
        "UA-66802-7",
        "UA-66802-3"
    ];
}
