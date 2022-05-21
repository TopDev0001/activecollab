<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\SystemModule;
use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('integration_singletons', SystemModule::NAME);

class OneLoginIntegrationController extends IntegrationSingletonsController
{
    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        if (!($this->active_integration instanceof OneLoginIntegration)) {
            return Response::CONFLICT;
        }
    }

    /**
     * Save credentials from xml file.
     *
     * @return OneLoginIntegration
     */
    public function credentials(Request $request)
    {
        $xml_code = $request->post('xml_code');

        if (!$xml_code) {
            return Response::BAD_REQUEST;
        }

        $xml_file = UploadedFiles::findByCode($xml_code);

        if (!$xml_file) {
            return Response::NOT_FOUND;
        }

        return $this->active_integration->setCredentials($xml_file);
    }

    /**
     * Enable.
     *
     * @return OneLoginIntegration
     */
    public function enable()
    {
        return $this->active_integration->enable();
    }

    /**
     * Disable.
     *
     * @return OneLoginIntegration
     */
    public function disable()
    {
        return $this->active_integration->disable();
    }
}
