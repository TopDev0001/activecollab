{lang language=$language}The email you sent couldn't be imported into ActiveCollab.{/lang}
================================================================================
{notification_logo}

<!-- Message Body -->
<h1 style="font-size: 16px; font-weight: bold; margin-top: 20px; margin-bottom: 16px;">
    {lang application_name=AngieApplication::getName() language=$language}The email you sent couldn't be imported into :application_name.{/lang}
</h1>

{if $bounce_reason}
    <p>{$bounce_reason}</p>
{/if}
