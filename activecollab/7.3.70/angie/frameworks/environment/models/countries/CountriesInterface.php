<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

interface CountriesInterface
{
    public function getCountries(): array;

    public function getCountryName(string $country_code): string;

    public function getCountryCode(string $country_name): string;

    public function getStateName(string $country_code): string;

    public function isValidCountryCode(string $country_code): bool;

    public function getEuCountries(): array;

    public function isValidEuCountryCode(string $country_code): bool;

    public function getUsStates(): array;

    public function isValidUsStateCode(string $us_state_code): bool;

    public function getCaStates(): array;

    public function isValidCaStateCode(string $ca_state_code): bool;

    public function getAuStates(): array;

    public function isValidAuStateCode(string $au_state_code): bool;

    public function isValidStateForCountry(
        string $country_code,
        string $state_code
    ): bool;
}
