[{$context->getProject()->getName()}] {lang name=$context->getName() language=$language}Task ':name' has been Completed{/lang}
================================================================================
<!-- Message Body -->
<h1 style="font-size: 16px; font-weight: bold; margin-top: 20px; margin-bottom: 16px;">
  {if $parents|@count gt 0}
    {lang name=$context->getName() context_resource_url=$context->getViewUrl() completed_parent_name=$completed_parent->getName() completed_parent_url=$completed_parent->getViewUrl()}
      The parent task &#10065;<a href=":completed_parent_url" style="text-decoration: line-through">:completed_parent_name</a> of the task &#10065;<a href=":context_resource_url">:name</a>, has been completed
    {/lang}
  {else}
    {lang name=$context->getName() context_resource_url=$context->getViewUrl() completed_parent_name=$completed_parent->getName() completed_parent_url=$completed_parent->getViewUrl()}
      The final parent task &#10065;<a href=":completed_parent_url" style="text-decoration: line-through">:completed_parent_name</a> of the task &#10065;<a href=":context_resource_url">:name</a>, has been completed
    {/lang}
  {/if}
</h1>

{if $parents|@count gt 0}
  {if $parents|@count gt 1}
    <strong style="font-size: 13px;">
      {lang total_left_parents=$parents|@count language=$language}There are :total_left_parents more parent tasks to go{/lang}:
    </strong>
  {else}
    <strong style="font-size: 13px;">
      {lang language=$language}There is 1 more parent task to go{/lang}:
    </strong>
  {/if}
  <br>
  {foreach $parents as $parent}
    &#10065;
    <a href="{$parent->getViewUrl()}">{$parent->getName()}</a>
    <br>
  {/foreach}
{/if}

<!-- Metadata -->
{notification_inspector context=$context recipient=$recipient link_style='color: #999999; text-decoration: none;'}
