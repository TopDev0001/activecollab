{lang language=$language}{$subject}{/lang}
================================================================================
{notification_logo}

{if $message}
<!-- Message Body -->
<h1 style="font-size: 16px; font-weight: bold; margin-top: 20px; margin-bottom: 16px;">{lang language=$language}{$message}{/lang}</h1>
{/if}

{if $additional_payload}
<!-- Payload -->
<pre>{$additional_payload}</pre>
{/if}
