{lang day=$paper_day->formatForUser($recipient, 0, $language) language=$language}Daily report for :day{/lang}
================================================================================
<h1 style="font-size: 16px; margin-top: 20px; margin-bottom: 16px;">
    {lang recipient_name=$recipient->getForcedFirstName() language=$language}Good morning, :recipient_name!{/lang}<br>
    <span
        style="color: #999999; font-weight: normal;">{lang day=$paper_day->formatForUser($recipient, 0, $language) language=$language}This is the daily recap for :day{/lang}</span>
</h1>

{if $late_data}
    <h2 style="font-weight: normal; font-size: 18px; border-bottom: 1px solid #666; padding-bottom: 6px; padding-top: 12px;">
        {lang language=$language}Late{/lang}
    </h2>
    <p>
        <strong style="font-size: 13px;">
            {if $late_data.late_tasks_count === 1}
                {lang language=$language}1 task is late:{/lang}
            {else}
                {lang num=$late_data.late_tasks_count language=$language}:num tasks are late:{/lang}
            {/if}
        </strong><br>

        {foreach $late_data.tasks_by_project as $project_details}
            <span style="font-size: 13px; font-weight: bold; color: #999999;">{$project_details.name}</span>
            <br>
            {foreach $project_details.tasks as $task}
                &#10065;
                <a href="{$task.permalink}">{$task.name}</a>
                <span
                    style="color: #ff0000;">{if $task.diff === -1}{lang language=$language}1 day late{/lang}{else}{lang num=$task.diff|abs language=$language}:num days late{/lang}{/if}</span>
                <br>
            {/foreach}
        {/foreach}
    </p>
{/if}


{if $today_data}
    <h2 style="font-weight: normal; font-size: 18px; border-bottom: 1px solid #666; padding-bottom: 6px; padding-top: 12px;">
        {lang language=$language}Today{/lang}
    </h2>

    {if $today_data.availability_records_count > 0}
        <p>
            <strong style="font-size: 13px;">
                {if $today_data.availability_records_count === 1}
                    {lang language=$language}One person unavailable:{/lang}
                {else}
                    {lang num=$today_data.availability_records_count language=$language}:num people unavailable:{/lang}
                {/if}
            </strong> <br>

            {foreach $today_data.availability_records as $availability_record}
                <span>
                    <a style="display: inline-block" href="{$availability_record.user_link}">
                        {$availability_record.user_name}
                    </a>,
                    {if $availability_record.end_date->getTimestamp() === $availability_record.start_date->getTimestamp()}
                        {$availability_record.availability_type_name} ({$availability_record.end_date|date:0:$recipient:$language})
                    {else}
                        {$availability_record.availability_type_name} ({$availability_record.start_date|date:0:$recipient:$language} - {$availability_record.end_date|date:0:$recipient:$language})
                    {/if}
                </span>
                <br>
            {/foreach}
        </p>
    {/if}


    {if $today_data.calendar_events_count > 0}
        <p>
            <strong style="font-size: 13px;">
                {if $today_data.calendar_events_count === 1}
                    {lang language=$language}1 event scheduled:{/lang}
                {else}
                    {lang num=$today_data.calendar_events_count language=$language}:num events scheduled:{/lang}
                {/if}
            </strong> <br>

            {foreach $today_data.calendar_events_by_calendar as $calendar_details}
                <span style="font-size: 13px; font-weight: bold; color: #999999;">{$calendar_details.name}</span>
                <br>
                {foreach $calendar_details.calendar_events as $calendar_event}
                    <a href="{$calendar_event.permalink}">{$calendar_event.name}</a>
                    {if $calendar_event.time} at {$calendar_event.time|time_vs_system_time:$recipient}<span style="color: #ff0000;">&#9873;</span>{/if}
                    <br>
                {/foreach}
            {/foreach}
        </p>
    {/if}

    {if $today_data.due_tasks_count > 0}
        <p>
            <strong style="font-size: 13px;">
                {if $today_data.due_tasks_count === 1}
                    {lang language=$language}1 task is due:{/lang}
                {else}
                    {lang num=$today_data.due_tasks_count language=$language}:num tasks are due:{/lang}
                {/if}
            </strong><br>

            {foreach $today_data.tasks_by_project as $project_details}
                <span style="font-size: 13px; font-weight: bold; color: #999999;">{$project_details.name}</span>
                <br>
                {foreach $project_details.tasks as $task}
                    &#10065;
                    <a href="{$task.permalink}">{$task.name}</a>
                    {if $task.due_on->getTimestamp() <= strtotime('today')}
                        {assign 'color' '#ff0000'} {* mark overdue tasks with red*}
                    {else}
                        {assign 'color' '#000000'}
                    {/if}

                    {if $task.due_on && $task.due_on->isToday()}
                        <span style="color: {$color}">{lang language=$language}Due Today{/lang}</span>
                    {elseif $task.start_on}
                        {if $task.start_on->isToday()}
                            <span style="color: {$color};">{lang language=$language}Start Today{/lang}</span>
                        {else}
                            <span style="color: {$color};">
                              {lang due_on=$task.due_on->formatDateForUser($recipient, 0, $language) start_on=$task.start_on->formatDateForUser($recipient, 0, $language) language=$language}:start_on &mdash; :due_on{/lang}
                            </span>
                        {/if}
                    {/if}

                    <br>
                {/foreach}
            {/foreach}
        </p>
    {/if}
{/if}

{if $prev_data}
    <h2 style="font-weight: normal; font-size: 18px; border-bottom: 1px solid #666; padding-bottom: 6px; padding-top: 12px;">
        {lang language=$language}Earlier{/lang}
    </h2>
    {foreach $prev_data as $project_id => $project_details}
        <h3 style="font-size: 16px;"><a href="{$project_details.permalink}">{$project_details.name}</a></h3>
        {if isset($project_details.task_completed)}
            <p>
                <strong style="font-size: 13px;">
                    {if count($project_details.task_completed) == 1}
                        {lang language=$language}1 task completed:{/lang}
                    {else}
                        {lang num=$project_details.task_completed|count language=$language}:num tasks completed:{/lang}
                    {/if}
                </strong><br>

                {foreach $project_details.task_completed as $task}
                    &#10004;
                    <a href="{$task.permalink}">{$task.name}</a>
                    <br>
                {/foreach}
            </p>
        {/if}

        {if isset($project_details.task_created)}
            <p>
                <strong style="font-size: 13px;">
                    {if count($project_details.task_created) == 1}
                        {lang language=$language}1 new task added:{/lang}
                    {else}
                        {lang num=$project_details.task_created|count language=$language}:num new tasks added:{/lang}
                    {/if}
                </strong><br>

                {foreach $project_details.task_created as $task}
                    &#10065;
                    <a href="{$task.permalink}"{if $task.is_completed} style="text-decoration: line-through"{/if}>{$task.name}</a>
                    <br>
                {/foreach}
            </p>
        {/if}

        {if isset($project_details.object_discussed)}
            <p>
                <strong style="font-size: 13px;">
                    {if count($project_details.object_discussed) == 1}
                        {lang language=$language}1 thing discussed:{/lang}
                    {else}
                        {lang num=$project_details.object_discussed|count language=$language}:num things discussed:{/lang}
                    {/if}
                </strong><br>

                {foreach $project_details.object_discussed as $object}
                    &#8226;
                    <a href="{$object.permalink}">{$object.name}</a>
                    <br>
                {/foreach}
            </p>
        {/if}

        {if isset($project_details.note_created)}
            <p>
                <strong style="font-size: 13px;">
                    {if count($project_details.note_created) == 1}
                        {lang language=$language}1 new note added:{/lang}
                    {else}
                        {lang num=$project_details.note_created|count language=$language}:num new notes added:{/lang}
                    {/if}
                </strong><br>

                {foreach $project_details.note_created as $note}
                    <a href="{$note.permalink}">{$note.name}</a>
                    <br>
                {/foreach}
            </p>
        {/if}

        {if isset($project_details.file_uploaded)}
            <p>
                <strong style="font-size: 13px;">
                    {if count($project_details.file_uploaded) == 1}
                        {lang language=$language}1 file uploaded:{/lang}
                    {else}
                        {lang num=$project_details.file_uploaded|count language=$language}:num files uploaded:{/lang}
                    {/if}
                </strong><br>

                {foreach $project_details.file_uploaded as $file}
                    <a href="{$file.permalink}">{$file.name}</a>
                    <br>
                {/foreach}
            </p>
        {/if}
    {/foreach}
{/if}
