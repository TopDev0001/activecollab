{$subject}
================================================================================
{notification_logo}

{if $payload}
    {if $payload.requires_auth}
        <p>{lang language=$language}For the past {$payload.days} days, we’ve been unsuccessfully trying to process the payment of your last bill{/lang}.</p>
        <p>{lang language=$language}Your account is now suspended, but you can continue with your Real Work by <a href="{$payload.link}">logging in to your workspace</a> and choosing one of the options for restoring your account. Note that all your data is safe and will be available to you upon successful payment{/lang}.</p>
        <p>{lang language=$language}If you require any assistance, feel free to contact our team{/lang}.</p>
        <p>ActiveCollab</p>
    {else}
        <p>{lang language=$language}For the past {$payload.days} days, we’ve been unsuccessfully trying to process the payment of your last bill{/lang}.</p>
        <p>{lang language=$language}Your account is now suspended, but you can continue with your Real Work by <a href="{$payload.link}">logging in to your workspace</a> and choosing one of the options for restoring your account. Note that all your data is safe and will be available to you upon successful payment{/lang}.</p>
        <p>ActiveCollab</p>
    {/if}
{/if}
