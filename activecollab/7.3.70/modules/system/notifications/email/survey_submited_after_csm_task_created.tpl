{$subject}
================================================================================
{notification_logo}

{if $payload}
    <p>Industry: {$payload.lead_industry}</p>
    <p>Size: {$payload.lead_size}</p>
    <p>Owner position: {$payload.lead_champion_role}</p>
    <p>Owner company name: {$payload.lead_name}</p>
    <p>Owner email: {$payload.owner_email}</p>
    <p>Owner name: {$payload.owner_name}</p>
{/if}
