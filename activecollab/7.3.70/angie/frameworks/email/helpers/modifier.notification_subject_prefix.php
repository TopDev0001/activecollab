<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Show notification subject prefix.
 *
 * @package angie.frameworks.email
 * @subpackage helpers
 */

/**
 * Return notification context prefix.
 *
 * @param  DataObject $context
 * @return string
 */
function smarty_modifier_notification_subject_prefix($context)
{
    return $context instanceof DataObject && method_exists($context, 'getNotificationSubjectPrefix') ? $context->getNotificationSubjectPrefix() : '';
}
