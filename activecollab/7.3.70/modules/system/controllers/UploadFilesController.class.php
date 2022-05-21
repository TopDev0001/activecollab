<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

AngieApplication::useController('fw_upload_files', EnvironmentFramework::NAME);

/**
 * Application level upload files controller.
 *
 * @package activeCollab.modules.system
 * @subpackage controllers
 */
class UploadFilesController extends FwUploadFilesController
{
}
