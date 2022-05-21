{$subject}
================================================================================
{notification_logo}

{if $payload}
    <p>
        We were unable to charge your account for the additional features you activated ({implode(', ', $payload.deactivated_add_ons)}). You can still access your account and use it, but without the additional features.
        If you'd like to continue using the additional features, you can visit your  <a href="{$payload.link}">subscription page</a> and purchase them..
    </p>
{/if}
