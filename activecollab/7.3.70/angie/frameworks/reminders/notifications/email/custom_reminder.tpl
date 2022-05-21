{$context|notification_subject_prefix}{lang type=$context->getVerboseType(true, $language) name=$context->getName() language=$language}Reminder for the :type: :name{/lang}
================================================================================
<h1 style="font-size: 16px; font-weight: bold; margin-top: 20px; margin-bottom: 16px;">
    {lang language=$language}Reminder for the task:{/lang} <span style="color: #ff0000">&#9873;</span> <br />
    <a href="{$context->getViewUrl()}">{$context->getName()}</a> <br />

    {if $context instanceof Task && $context->getDueOn()}
        {if $context->getDueOn()->getTimeStamp() <= strtotime('today')}
            {assign 'color' '#ff0000' }
        {else}
            {assign 'color' '#000000' }
        {/if}

        {if $context->getStartOn() && !$context->getDueOn()->isSameDay($context->getStartOn())}
            <span style="color: {$color}; font-weight: normal;">{lang due_on=$context->getDueOn()->formatDateForUser($recipient, 0, $language) start_on=$context->getStartOn()->formatDateForUser($recipient, 0, $language) language=$language}From :start_on to :due_on{/lang}</span>
        {else}
            <span style="color: {$color}; font-weight: normal;">{lang due_on=$context->getDueOn()->formatDateForUser($recipient, 0, $language) language=$language}Due on :due_on{/lang}</span>
        {/if}
    {/if}
</h1>

{if $reminder->getComment()}
<p>{$reminder->getComment()|escape|nl2br nofilter}</p>
{/if}

{notification_inspector context=$context subcontext=$reminder recipient=$recipient link_style='color: #999999; text-decoration: none;'}
