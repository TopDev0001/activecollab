<?php
/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */


namespace ActiveCollab\ActiveCollabJobs\Utils\GoogleAnalytics;

interface GoogleMeasurementInterface
{
    const VARIATION_NEW = 'new';
    const VARIATION_RECURRING = 'recurring';

    public function addItem(
        string $name,
        float $price,
        string $sku,
        string $variation = GoogleMeasurementInterface::VARIATION_NEW,
        int $quantity = 1
    ): void;
}
