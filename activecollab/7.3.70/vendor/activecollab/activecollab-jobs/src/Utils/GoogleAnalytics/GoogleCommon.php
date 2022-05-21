<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Utils\GoogleAnalytics;

abstract class GoogleCommon implements GoogleCommonInterface
{
    private $google_client_id;
    private $account_id;
    private $ip;
    private $country;

    public function __construct(
        int $account_id,
        string $country,
        ?string $google_client_id = null,
        ?string $ip = null
    ) {
        $this->google_client_id = $google_client_id;
        $this->account_id = $account_id;
        $this->ip = $ip;
        $this->country = $country;
    }

    protected function sendRequest(array $data): void
    {
        foreach (GoogleCommonInterface::TRACKING_IDS as $tid) {
            $data['tid'] = $tid;

            $querystring = utf8_encode(http_build_query($data));
            $url = "{$this->getUrl()}?{$querystring}" . "&cid=" . rand(0, 100000) . "&z=" . rand(0, 100000);
            $userAgent = 'Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_exec($ch);

            $error = curl_error($ch);

            if ($error) {
                // @todo log error
            }

            curl_close($ch);
        }
    }

    protected function getCommonData($type): array
    {
        $data = [
            'v' => 1,
            'cid' => $this->google_client_id,
            't' => $type,
            'uip' => $this->ip,
        ];

        if ($this->account_id) {
            $data['uid'] = $this->account_id;
        }

        if (empty($data['uip'])) {
            unset($data['uip']);
            $data['geoid'] = strtoupper($this->country);
        }

        return $data;
    }

    private function getUrl(): string
    {
        return GoogleCommonInterface::URL;
    }
}
