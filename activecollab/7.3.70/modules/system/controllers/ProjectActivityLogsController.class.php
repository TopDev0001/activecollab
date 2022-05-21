<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\SystemModule;

AngieApplication::useController('project', SystemModule::NAME);

class ProjectActivityLogsController extends ProjectController
{
    /**
     * List project activities.
     */
    public function index()
    {
    }

    /**
     * Project activities as RSS feed.
     */
    public function rss()
    {
    }
}
