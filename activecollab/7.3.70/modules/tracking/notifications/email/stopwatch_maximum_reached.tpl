{lang language=$language}Stopwatch alert{/lang}
================================================================================
{notification_logo}

<p>{lang name=$recipient->getForcedFirstName() language=$language}Hi :name{/lang},</p>
<p>{lang language=$language}<b>Stopwatch is paused in:{/lang}
<br/>
<a href="{$url}">{$description}</a></b>
</p>
<p>{lang language=$language}Stopwatch for this item has reached the time limit (99:59).{/lang}</p>
<p>{lang language=$language}ActiveCollab Team{/lang}</p>
