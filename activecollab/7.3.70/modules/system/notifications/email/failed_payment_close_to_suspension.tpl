{$subject}
================================================================================
{notification_logo}

{if $payload}
    {if $payload.requires_auth}
        <p>{lang language=$language}This is the {$payload.attempts}{if $payload.attempts == 1}st{elseif $payload.attempts == 2}nd{elseif $payload.attempts == 3}rd{elseif $payload.attempts >= 4}th{/if} time we were unsuccessful in processing the payment of your last bill{/lang}.</p>
        <p>{lang language=$language}Please note that your account will be suspended if you don’t authenticate your payment in the next {$payload.remain_days} {if $payload.remain_days > 1}days{else}day{/if}. This means that none of your team members will be able to access your ActiveCollab workspace{/lang}.</p>
        <p>{lang language=$language}If you require any assistance, feel free to contact our team{/lang}.</p>
        <p>ActiveCollab</p>
    {else}
        <p>{lang language=$language}This is the {$payload.attempts}{if $payload.attempts == 1}st{elseif $payload.attempts == 2}nd{elseif $payload.attempts == 3}rd{elseif $payload.attempts >= 4}th{/if} time we were unsuccessful in processing the payment of your last bill{/lang}.</p>
        <p>{lang language=$language}Please note that your account will be suspended if we don’t process your payment in the next {$payload.remain_days} {if $payload.remain_days > 1}days{else}day{/if}. This means that none of your team members will be able to access your ActiveCollab workspace{/lang}.</p>
        <p>{lang language=$language}We’ll still keep trying to automatically charge your account for the next {$payload.remain_days} {if $payload.remain_days > 1}days{else}day{/if}. At the same time, you can simply <a href="{$payload.link}">log in to your workspace</a> and retry the payment yourself{/lang}.</p>
        <p>ActiveCollab</p>
    {/if}
{/if}
