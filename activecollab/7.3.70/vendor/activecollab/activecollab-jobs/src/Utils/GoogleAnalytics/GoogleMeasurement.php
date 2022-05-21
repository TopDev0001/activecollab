<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Utils\GoogleAnalytics;

class GoogleMeasurement extends GoogleCommon implements GoogleMeasurementInterface
{
    private $reference;
    private $total_amount;
    private $currency;

    private $items = [];

    public function __construct(
        string $reference,
        float $total_amount,
        string $currency,
        string $country,
        int $account_id,
        ?string $ip = null,
        ?string $google_client_id = null
    ) {
        parent::__construct(
            $account_id,
            $country,
            $google_client_id,
            $ip
        );

        $this->reference = $reference;
        $this->total_amount = $total_amount;
        $this->currency = strtoupper($currency);
    }

    private function getItems(): array
    {
        return !empty($this->items) ? $this->items : [];
    }

    public function addItem(
        string $name,
        float $price,
        string $sku,
        string $variation = GoogleMeasurementInterface::VARIATION_NEW,
        int $quantity = 1
    ): void {
        $this->items[$sku] = array_merge(
            $this->getCommonData(GoogleCommonInterface::HIT_TYPE_ITEM),
            [
                'ti' => $this->reference,
                'in' => $name,
                'ip' => $price,
                'iq' => $quantity,
                'ic' => $sku,
                'iv' => $variation,
                'cu' => $this->currency,
            ]
        );
    }

    public function send(): void
    {
        $items = $this->getItems();

        if (!empty($items)) {
            $this->sendRequest($this->getTransactionData());

            foreach ($items as $item) {
                $this->sendRequest($item);
            }
        }
    }

    private function getTransactionData(): array
    {
        return array_merge(
            $this->getCommonData(GoogleCommonInterface::HIT_TYPE_TRANSACTION),
            [
                'ti' => $this->reference,
                'tr' => $this->total_amount,
                'ts' => 0,
                'tt' => 0,
                'cu' => $this->currency,
            ]
        );
    }
}
