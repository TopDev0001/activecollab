{lang language=$language}Project Budget Alert{/lang}
================================================================================
{notification_logo}

<p>{lang name=$recipient->getForcedFirstName() language=$language}Hi, :name!{/lang}</p>
<p>{lang language=$language}The spent amount of your project {$projectName} has reached {$threshold}% of its budget.{/lang}</p>
<p><a href="{$projectUrl}">{lang language=$language}You can review it here.{/lang}</a></p>
