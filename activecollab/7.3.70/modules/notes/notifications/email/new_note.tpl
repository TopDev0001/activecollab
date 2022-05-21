[{$context->getProject()->getName()}] {lang name=$context->getName() language=$language}New Note ":name"{/lang}
================================================================================
<!-- Message Body -->
<h1 style="font-size: 16px; font-weight: bold; margin-top: 20px; margin-bottom: 16px;">
    {lang author=$context->getCreatedBy()->getDisplayName() language=$language}:author invited you to the note:{/lang}
    <br/>
    <a href="{$context->getViewUrl()}">{$context->getName()}</a>
</h1>

<!-- Description -->
<div style="background: #FFF7C4; padding-top: 0; padding-bottom: 0; padding-left: 12px; padding-right: 12px; border-width: 1px; border-color: #E3DA9C; border-style: solid;">
    <p><strong>{$context->getName()}</strong></p>
    {$context->getFormattedBody('email')|jwt_signed_file:$recipient nofilter}
    {notification_attachments_table object=$context recipient=$recipient}
</div>

<!-- Metadata -->
{notification_inspector context=$context recipient=$recipient link_style='color: #999999; text-decoration: none;'}