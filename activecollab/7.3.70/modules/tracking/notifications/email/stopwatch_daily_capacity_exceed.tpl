{lang language=$language}Stopwatch alert{/lang}
================================================================================
{notification_logo}

<p>{lang name=$recipient->getForcedFirstName() language=$language}Hi :name{/lang},</p>
<p>{lang language=$language}Looks like your Stopwatch has been running over your daily capacity{/lang} ({$daily_capacity}h).
    <br>{lang language=$language}Slow down!{/lang}</p>
<p>{lang language=$language}In case you forgot to pause your Stopwatch, check it out{/lang} <a href="{$url}">{lang language=$language}here{/lang}</a>.</p>
<p>{lang language=$language}ActiveCollab Team{/lang}</p>
