<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Invoice comment wrapper.
 *
 * @package activeCollab.modules.invoicing
 * @subpackage helpers
 */

/**
 * Render invoice comment.
 *
 * @param  array  $params
 * @param  string $content
 * @param  Smarty $smarty
 * @param  bool   $repeat
 * @return mixed
 */
function smarty_block_notification_invoice_comment($params, $content, &$smarty, &$repeat)
{
    if ($repeat) {
        return '';
    }

    //$context = array_required_var($params, 'context', true, 'ApplicationObject');
    $recipient = array_required_var($params, 'recipient', true, 'IUser');

    if ($content) {
        return '<div style="margin-top: 16px;"><div style="font-weight: bold;">' . lang('Note', null, null, $recipient->getLanguage()) . ':</div><div style="padding: 10px"> ' . nl2br(clean($content)) . '</div></div>';
    }
}
