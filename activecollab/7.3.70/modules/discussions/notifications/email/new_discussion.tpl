[{$context->getProject()->getName()}] {lang name=$context->getName() language=$language}Discussion ':name' has been Created{/lang}
================================================================================
<!-- Message Body -->
<h1 style="font-size: 16px; font-weight: bold; margin-top: 20px; margin-bottom: 16px;">
    {lang author=$context->getCreatedBy()->getDisplayName() language=$language}:author invited you to the discussion:{/lang}
    <br/>
    <a href="{$context->getViewUrl()}">{$context->getName()}</a>
</h1>

<!-- Description -->
{$context->getFormattedBody('email')|jwt_signed_file:$recipient nofilter}
{notification_attachments_table object=$context recipient=$recipient}

<!-- Metadata -->
{notification_inspector context=$context recipient=$recipient link_style='color: #999999; text-decoration: none;'}