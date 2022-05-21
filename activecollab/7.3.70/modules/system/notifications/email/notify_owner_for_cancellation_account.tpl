{$subject}
================================================================================
{notification_logo}

{if $payload}
    <p>Hi {$payload.owner.first_name},</p>
    {if $payload.is_active || $payload.is_failed_payment}
        <p>Your subscription has been canceled{if !$payload.is_same_owner} by {$payload.cancelled_by.full_name}{/if}.</p>
        {if $payload.is_active}
            <p>This action can be revoked until {$payload.expiration_date|date:0:$recipient:$language} (go to <a href="{$payload.subscription_page_url}">Billing page</a>).</p>
            <p>After this date, we'll email you your data, but you'll no longer be able to use this ActiveCollab account.</p>
        {/if}
    {/if}
    {if $payload.is_trial}
        <p>Your ActiveCollab trial has been canceled{if !$payload.is_same_owner} by {$payload.cancelled_by.full_name}{/if}.</p>
    {/if}
{/if}

<p>We’re sorry to see you leave.</p>
<p>Thank you for all the time you spent with us!</p>

<p>ActiveCollab Team</p>
