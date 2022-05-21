[{$calendar->getName()}] {lang name=$context->getName() language=$language}New event: :name{/lang}
================================================================================
<h1 style="font-size: 16px; font-weight: bold; margin-top: 20px; margin-bottom: 16px;">
	{lang author=$context->getCreatedBy()->getDisplayName() language=$language}:author invited you to the event:{/lang} <span style="color: #ff0000">&#9873;</span><br />
	<a href="{$context->getViewUrl()}">{$context->getName()}</a> <br />
	<span style="color: #ff0000; font-weight: normal;">{$starts_on|date:0:$recipient:$language}{if $starts_on_time} {$starts_on|time_vs_system_time:$recipient}{/if}</span>
</h1>

{if $context->getNote()}
<p><strong>{lang language=$language}Note:{/lang}</strong> {$context->getNote()}</p>
{/if}

{notification_inspector context=$context recipient=$recipient link_style='color: #999999; text-decoration: none;'}