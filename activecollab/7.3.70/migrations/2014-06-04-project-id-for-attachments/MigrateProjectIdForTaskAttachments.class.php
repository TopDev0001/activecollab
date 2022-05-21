<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

require_once __DIR__ . '/abstract/MigrateUpdateAttachmentProjectIdByType.class.php';

/**
 * Migrate project_id and is_hidden_from_client values for task attachments.
 *
 * @package activeCollab.modules.system
 * @subpackage migrations
 */
class MigrateProjectIdForTaskAttachments extends MigrateUpdateAttachmentProjectIdByType
{
    /**
     * @var string
     */
    protected $type_table = 'tasks';
    protected $type_class = 'Task';
}
