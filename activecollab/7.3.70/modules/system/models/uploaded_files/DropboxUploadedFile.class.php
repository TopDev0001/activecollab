<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
class DropboxUploadedFile extends RemoteUploadedFile
{
    use IDropboxFileImplementation;
}
