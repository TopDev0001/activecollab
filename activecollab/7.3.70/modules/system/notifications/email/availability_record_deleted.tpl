{lang language=$language}Availability record deleted{/lang}
================================================================================

<h1 style="font-size: 16px; font-weight: bold; margin-top: 20px; margin-bottom: 16px;">
    {lang language=$language}Availability record deleted{/lang}
</h1>
<p>
    {if $availability_record->getDuration() === 1}
        {lang availability_name=$availability_type->getName() end_date=$availability_record->getEndDate()|date:0:$recipient:$language deleted_by_name=$sender->getDisplayName() language=$language}Your availability record ":availability_name (:end_date)" has been deleted by :deleted_by_name{/lang}.
    {else}
        {lang availability_name=$availability_type->getName() start_date=$availability_record->getStartDate()|date:0:$recipient:$language end_date=$availability_record->getEndDate()|date:0:$recipient:$language deleted_by_name=$sender->getDisplayName() language=$language}Your availability record ":availability_name (:start_date - :end_date)" has been deleted by :deleted_by_name{/lang}.
    {/if}
</p>
<p>
    {lang user_profile_url=$availability_record->getUser()->getViewUrl() language=$language}Go to <a href=":user_profile_url">your profile</a>{/lang}
</p>
