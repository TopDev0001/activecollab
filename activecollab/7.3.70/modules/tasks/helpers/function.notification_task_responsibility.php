<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * notification_task_responsibility helper implementation.
 *
 * @package activecollab.modules.tasks
 * @subpackage helpers
 */

/**
 * Render new task responisble person on notification.
 *
 * @param  array  $params
 * @param  Smarty $smarty
 * @return string
 */
function smarty_function_notification_task_responsibility($params, &$smarty)
{
    $context = array_required_var($params, 'context', false, 'ApplicationObject');
    $recipient = array_required_var($params, 'recipient', false, 'IUser');

    $language = $recipient->getLanguage();

    $result = '';

    /** @var Task $context */
    if ($context->getAssignee() instanceof IUser) {
        // Recipient is responsible
        if ($context->isAssignee($recipient)) {
            $result = lang('<u>You are responsible</u> for this :type!', [
                'type' => $context->getVerboseType(true, $language),
            ], true, $language);

            // Someone else is assignee
        } elseif ($context->getAssignee() instanceof User) {
            $result = lang(':responsible_name is responsible for this :type.', [
                'type' => $context->getVerboseType(true, $language),
                'responsible_name' => $context->getAssignee()->getDisplayName(true),
            ], true, $language);
        }

        if ($context->getDueOn()) {
            $result .= ' ' . lang('It is due on <u>:due_on</u>', [
                'due_on' => $context->getDueOn()->formatForUser($recipient, 0),
            ], true, $language);
        }
    }

    return $result;
}
