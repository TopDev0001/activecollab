{lang language=$language}You've been invited to join{/lang}
================================================================================
{notification_logo}

<!-- Message Body -->
<h1 style="font-size: 16px; font-weight: bold; margin-top: 20px; margin-bottom: 16px;">
{if $invited_to instanceof Project}
    {lang invited_by=$invited_by->getDisplayName() owner_company=$owner_company->getName() project_name=$invited_to->getName() language=$language}:invited_by from :owner_company has invited you to ActiveCollab to work together on the :project_name project.{/lang}
{else}
    {lang invited_by=$invited_by->getDisplayName() owner_company=$owner_company->getName() language=$language}:invited_by from :owner_company has invited you to ActiveCollab to work together on projects.{/lang}
{/if}
</h1>

<p><a href="{$invitation->getAcceptUrl()}">{lang language=$language}Click here to log in{/lang}</a></p>

<p style="font-size: 14px; color: #A9A9A9; text-decoration: none;">{lang invited_by_email=$sender->getEmail() language=$language}ActiveCollab is an online project management tool. If you're not sure why you got this email, please contact <a href="mailto::invited_by_email">:invited_by_email</a>.{/lang}</p>
