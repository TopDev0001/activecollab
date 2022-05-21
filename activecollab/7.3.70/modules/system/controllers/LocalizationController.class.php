<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

AngieApplication::useController('fw_localization', EnvironmentFramework::NAME);

/**
 * Localization controller.
 *
 * @package activeCollab.modules.system
 * @subpackage controllers
 */
class LocalizationController extends FwLocalizationController
{
    public function show_countries()
    {
        return (new Countries())->getCountries();
    }

    public function show_eu_countries()
    {
        return (new Countries())->getEuCountries();
    }

    public function show_states()
    {
        $countries = new Countries();

        return [
            'au' => $countries->getAuStates(),
            'ca' => $countries->getCaStates(),
            'us' => $countries->getUsStates(),
        ];
    }
}
