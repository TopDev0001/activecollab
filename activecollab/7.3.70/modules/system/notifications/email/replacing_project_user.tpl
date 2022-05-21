{lang replacing_user_name=$replacing_user->getDisplayName() language=$language}You are now responsible for :replacing_user_name's tasks{/lang}
================================================================================
<!-- Message Body -->
<h1 style="font-size: 16px; font-weight: bold; margin-top: 20px; margin-bottom: 16px;">
    {lang replacing_user_name=$replacing_user->getDisplayName() language=$language}You are now responsible for :replacing_user_name's tasks in the project:{/lang}
    <br/>
    <a href="{$context->getViewUrl()}">{$context->getName()}</a> <br/>
</h1>

{if $open_tasks->count() > 1}
    <p>{lang task_number=$open_tasks->count() language=$language}You have been assigned to :task_number tasks:{/lang}</p>
{else}
    <p>{lang language=$language}You have been assigned to one task:{/lang}</p>
{/if}

<p>
    {foreach from=$open_tasks item=open_task name=open_tasks}
        &#10065; <a href="{$open_task->getViewUrl()}">{$open_task->getName()}</a>{if $open_task->getDueOn()} <span style="color: #ff0000;">{$open_task->getDueOn()|date:0:$recipient:$language}</span>{/if}{if !$smarty.foreach.open_tasks.last}
        <br>
    {/if}
    {/foreach}
</p>

<p>{lang my_task_url=$recipient_tasks_url language=$language}These tasks have been added to <a href=":my_task_url">My Tasks</a> in your ActiveCollab.{/lang}</p>
