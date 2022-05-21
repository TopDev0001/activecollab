<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\App\RootUrl;

use ActiveCollab\Foundation\App\AccountId\AccountIdResolverInterface;
use ActiveCollab\Foundation\Urls\Url;

class RootUrl extends Url implements RootUrlInterface
{
    private AccountIdResolverInterface $account_id_resolver;

    public function __construct(string $url, AccountIdResolverInterface $account_id_resolver)
    {
        parent::__construct($url);

        $this->account_id_resolver = $account_id_resolver;
    }

    public function isInternalUrl(string $url_to_check): bool
    {
        return str_starts_with($url_to_check, $this->getUrl());
    }

    public function getRelativeUrl(string $url): ?string
    {
        if (!$this->isInternalUrl($url)) {
            return null;
        }

        $root_url_length = mb_strlen($this->getUrl());

        return mb_substr($url, $root_url_length, mb_strlen($url) - $root_url_length);
    }

    public function expandRelativeUrl(string $from_relative_url): string
    {
        if (empty($from_relative_url)) {
            return $this->getUrl();
        }

        if (mb_substr($from_relative_url, 0, 1) === '/') {
            $from_relative_url = mb_substr($from_relative_url, 1);
        }

        return $this->getUrl() . '/' . $this->cleanUpLeadingAccountIds($from_relative_url);
    }

    private function cleanUpLeadingAccountIds(string $from_relative_url): string
    {
        $bits = explode('/', $from_relative_url);

        $first_element_key = 0;

        while (!empty($bits[$first_element_key])
            && ctype_digit($bits[$first_element_key])
            && (int) $bits[$first_element_key] === $this->account_id_resolver->getAccountId()
        ) {
            unset($bits[$first_element_key]);
            $first_element_key++;
        }

        return implode('/', $bits);
    }
}
