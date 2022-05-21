{lang language=$language}Disk space limit exceeded{/lang}
================================================================================

<h1 style="font-size: 16px; font-weight: bold; margin-top: 20px; margin-bottom: 16px;">
    {lang language=$language}Disk space limit exceeded{/lang}
</h1>
{if $is_legacy}
<p>{lang language=$language disk_space_limit=$disk_space_limit}We were unable to create a recurring task with attachments because you have reached your plan’s storage limit (:disk_space_limit). Please free up some space, or switch to the Per Seat plan.{/lang}</p>
{else}
    <p>{lang language=$language disk_space_limit=$disk_space_limit}We were unable to create a recurring task with attachments because you have reached your storage limit (:disk_space_limit). Please free up some space, or buy additional storage <a href="{$storage_add_ons_url}">here</a>.{/lang}</p>
{/if}
