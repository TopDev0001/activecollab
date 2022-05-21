{$subject}
================================================================================
{notification_logo}

{if $payload}
    <p>{lang language=$language}Hi {$payload.name}{/lang},</p>
    <p>{lang language=$language}Hope you're enjoying your new workspace! Has ActiveCollab met your team's expectations?{/lang}</p>
    <p>{lang language=$language}Two weeks are up and your trial is almost over. After tomorrow, only you (but not your team) will be able to access your ActiveCollab workspace.{/lang}</p>
    <p>{lang language=$language}If you want to continue using ActiveCollab, you can subscribe for a monthly or yearly plan.{/lang}</p>
    <p><a href="{$payload.link}">{lang language=$language}Buy ActiveCollab now{/lang}</a></p>
    <p>{lang language=$language}We're here to help if you need us.{/lang}</p>
    <p>ActiveCollab Team</p>
{/if}
