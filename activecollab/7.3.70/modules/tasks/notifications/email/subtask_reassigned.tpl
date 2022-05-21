[{$project->getName()}] {lang name=$subtask->getName()|excerpt language=$language}':name' Subtask has been Assigned to You{/lang}
================================================================================
<!-- Message Body -->
<h1 style="font-size: 16px; font-weight: bold; margin-top: 20px; margin-bottom: 16px;">
    {if $recipient instanceof User && $subtask->isAssignee($recipient)}
        {lang author=$sender->getDisplayName() language=$language}:author assigned you the subtask:{/lang} &#10065;
        <br/>
    {else}
        {lang author=$sender->getDisplayName() language=$language}:author created the subtask:{/lang} &#10065;
        <br/>
    {/if}
    {lang subtask_id=$subtask->getId() subtask_name=$subtask->getBody() task_url=$context->getViewUrl() task_name=$context->getName() language=$language} <a href=":task_url#subtask-:subtask_id}">:subtask_name</a> in the task <a href=":task_url" style="color: #999999;">:task_name</a> task{/lang}
</h1>

<!-- Metadata -->
{notification_inspector context=$context subcontext=$subtask recipient=$recipient link_style='color: #999999; text-decoration: none;'}