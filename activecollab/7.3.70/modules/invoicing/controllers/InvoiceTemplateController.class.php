<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

use Angie\Http\Request;
use Angie\Http\Response;

/**
 * Class InvoiceTemplateController.
 */
class InvoiceTemplateController extends AuthRequiredController
{
    /**
     * @var InvoiceTemplate
     */
    protected $active_template;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        $this->active_template = new InvoiceTemplate();
    }

    /**
     * Return invoice template settings.
     *
     * @return int|InvoiceTemplate
     */
    public function show_settings(Request $request, User $user)
    {
        return $this->active_template->canView($user) ? $this->active_template : Response::NOT_FOUND;
    }

    /**
     * Save invoice template settings.
     *
     * @return int|InvoiceTemplate
     * @throws Exception
     */
    public function save_settings(Request $request, User $user)
    {
        if ($this->active_template->canEdit($user)) {
            $put = $request->put();

            if (array_key_exists('uploaded_logo_code', $put)) {
                $uploaded_file = UploadedFiles::findByCode($put['uploaded_logo_code']);

                if ($uploaded_file instanceof UploadedFile && $this->active_template->isSupportedLogoImage($uploaded_file->getName(), $uploaded_file->getMimeType(), $uploaded_file->getSize())) {
                    $put['logo'] = $uploaded_file;
                } else {
                    return Response::BAD_REQUEST;
                }
            } else {
                unset($put['logo']);
            }

            $this->active_template->setAttributes($put);
            $this->active_template->save();

            return $this->active_template;
        }

        return Response::NOT_FOUND;
    }
}
