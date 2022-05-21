{lang language=$language}Webhook automatically disabled{/lang}
================================================================================
{notification_logo}

<h1 style="font-size: 16px; font-weight: bold; margin-top: 20px; margin-bottom: 16px;">
    {lang language=$language}Webhook disabled{/lang}
</h1>
<p>{lang webhook_name=$webhook->getName() language=$language}Webhook <b>":webhook_name"</b> has been disabled{/lang}. {lang language=$language}Webhooks are disabled when our systems can't reach them for several hours{/lang}.</p>
<p>{lang webhooks_url=$webhooks_url webhook_name=$webhook->getName() language=$language}To enable the webhook, open the <a href=":webhooks_url">Webhooks page</a>, select the <b>Edit</b> option for the ":webhook_name" webhook, and enable it{/lang}.</p>
