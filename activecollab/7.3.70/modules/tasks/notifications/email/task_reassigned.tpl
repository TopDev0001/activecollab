[{$context->getProject()->getName()}] {lang name=$context->getName() language=$language}Task ':name' has been Assigned to You{/lang}
================================================================================
<!-- Message Body -->
<h1 style="font-size: 16px; font-weight: bold; margin-top: 20px; margin-bottom: 16px;">
    {lang author=$sender->getDisplayName() language=$language}:author assigned you the task:{/lang} &#10065; <br/>
    <a href="{$context->getViewUrl()}">{$context->getName()}</a>
    {if $context->getDueOn()}
        <br/>
        {if $context->getDueOn()->getTimeStamp() <= strtotime('today')}
            {assign 'color' '#ff0000' }
        {else}
            {assign 'color' '#000000' }
        {/if}
        {if $context->getStartOn() && !$context->getDueOn()->isSameDay($context->getStartOn())}
            <span style="color: {$color}; font-weight: normal;">
                {lang due_on=$context->getDueOn()->formatDateForUser($recipient, 0, $language) start_on=$context->getStartOn()->formatDateForUser($recipient, 0, $language) language=$language}:start_on &mdash; :due_on{/lang}
            </span>
        {else}
            <span style="color: {$color}; font-weight: normal;">
                {lang due_on=$context->getDueOn()->formatDateForUser($recipient, 0, $language) language=$language}Due on :due_on{/lang}
            </span>
        {/if}
    {/if}
</h1>

<!-- Description -->
{$context->getFormattedBody('email') nofilter}
{notification_attachments_table object=$context recipient=$recipient}

<!-- Metadata -->
{notification_inspector context=$context recipient=$recipient link_style='color: #999999; text-decoration: none;'}