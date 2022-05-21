<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Render inspector table for email notifications.
 *
 * @package angie.frameworks.email
 * @subpackage helpers
 */

/**
 * Render notification inspector.
 *
 * @param  array  $params
 * @param  Smarty $smarty
 * @return string
 */
function smarty_function_notification_inspector($params, &$smarty)
{
    /** @var ApplicationObject|ISubscriptions $context */
    $context = array_required_var($params, 'context', true, 'ApplicationObject');

    /** @var ApplicationObject $subcontext */
    $subcontext = array_var($params, 'subcontext', null, true);

    /** @var IUser $recipient */
    $recipient = array_required_var($params, 'recipient', true, 'IUser');
    $language = $recipient->getLanguage();

    $link_style = array_var($params, 'link_style', '');

    $properties = new \Angie\NamedList();

    $subscribers = $context instanceof ISubscriptions ? $context->getSubscribers() : null;

    if ($subscribers && is_foreachable($subscribers)) {
        AngieApplication::useHelper('notification_recipients', EmailFramework::NAME, 'modifier');

        $properties->add('recipients', [
            'label' => lang('People in this :object_type', ['object_type' => $context->getVerboseType(true, $language)], true, $language),
            'value' => smarty_modifier_notification_recipients($subscribers, null, $link_style, $language),
        ]);
    }

    \Angie\Events::trigger('on_notification_inspector', [&$context, &$subcontext, &$recipient, &$language, &$properties, $link_style]);

    $rendered_properties = [];

    foreach ($properties as $property) {
        $rendered_properties[] = $property['label'] . ': ' . $property['value'];
    }

    return '<div class="metadata" style="color: #999999; font-size: 14px; line-height: 21px;"><p>' . implode('<br>', $rendered_properties) . '</p></div>';
}
