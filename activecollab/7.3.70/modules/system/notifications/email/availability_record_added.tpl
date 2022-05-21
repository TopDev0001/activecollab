{if $is_created_by_another_user}
    {lang user_name=$created_by->getDisplayName() language=$language}:user_name added a new availability record for you{/lang}
{else}
    {lang user_name=$created_by->getDisplayName() language=$language}:user_name added a new availability record{/lang}
{/if}
================================================================================

<h1 style="font-size: 16px; font-weight: bold; margin-top: 20px; margin-bottom: 16px;">
    {if $is_created_by_another_user}
        {lang user_name=$created_by->getDisplayName() language=$language}:user_name added a new availability record for you{/lang}
    {else}
        {lang user_name=$created_by->getDisplayName() language=$language}:user_name added a new availability record{/lang}
    {/if}
</h1>
<p>
    <strong>{lang language=$language}Availability type{/lang}:</strong> {$availability_type->getName()} ({$availability_type->getVerboseLevel($language)})
</p>
<p>
    <strong>{lang language=$language}Date{/lang}:</strong>
    {if $duration === 1}
        {$availability_record->getEndDate()|date:0:$recipient:$language} ({lang language=$language}One workday{/lang})
    {else}
        {$availability_record->getStartDate()|date:0:$recipient:$language} - {$availability_record->getEndDate()|date:0:$recipient:$language} ({lang duration=$duration language=$language}:duration workdays{/lang})
    {/if}
</p>
<p>
    <strong>{lang language=$language}Message{/lang}:</strong>
    {if $availability_record->getMessage()}
        {$availability_record->getMessage()}
    {else}
        <i>{lang language=$language}No message {/lang}</i>
    {/if}
</p>
<p>
    {if $recipient->getId() !== $availability_record->getUserId()}
        {lang user_profile_url=$created_for->getViewUrl() user_name=$created_for->getFirstName() language=$language}Go to <a href=":user_profile_url">:user_name's profile</a>{/lang}
    {else}
        {lang user_profile_url=$created_for->getViewUrl() language=$language}Go to <a href=":user_profile_url">your profile</a>{/lang}
    {/if}
</p>
