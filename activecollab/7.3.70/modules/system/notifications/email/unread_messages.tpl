{lang language=$language}New unread messages{/lang}
================================================================================
{notification_logo}

{if $total}
    <p>
        {if $total === 1 }
            <strong style="font-size: 16px;"> {lang language=$language}Hey, you have one unread message in the past 90 minutes:{/lang}</strong>
        {else}
            <strong style="font-size: 16px;"> {lang total=$total language=$language}Hey, you have :total unread messages in the past 90 minutes:{/lang}</strong>
        {/if}
        <div style="margin-left: 15px;">
        {foreach from=$messages_by_conversations item=messages_by_conversation}
            <div style="display: flex; align-items: center; margin: 10px 0px;">
                <img src="{$messages_by_conversation.avatar_url}" width="36" height="36" alt="{$messages_by_conversation.name}" style="border-radius: 99999px; margin-right: 10px;"/>
                <span><strong>{$messages_by_conversation.name}:</strong> {$messages_by_conversation.count} {if $messages_by_conversation.count gt 1 }{lang language=$language}messages{/lang} {else}{lang language=$language}message{/lang}{/if}</span>
            </div>
        {/foreach}
        </div>
    </p>

    <strong>{lang app_url=$application_url language=$language} <a href=":app_url">Open in ActiveCollab</a>{/lang}</strong>
{/if}
