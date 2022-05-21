{$subject}
================================================================================
{notification_logo}

{if $payload}
    <p>{lang language=$language}Hi {$payload.name}{/lang},</p>
    <p>{lang language=$language}Thanks for trying ActiveCollab. We hope you enjoyed it!{/lang}</p>
    <p>{lang language=$language}Your trial has expired, but you can continue with your Real Work by choosing a subscription plan.{/lang}</p>
    <p><a href="{$payload.link}">{lang language=$language}Buy ActiveCollab now{/lang}</a></p>
    <p>{lang language=$language}Real Work awaits, let's make it happen!{/lang}</p>
    <p>ActiveCollab Team</p>
{/if}
