{lang project_name=$context->getName() language=$language}You've been invited to join :project_name{/lang}
================================================================================
{notification_logo}

<!-- Message Body -->
<h1 style="font-size: 16px; font-weight: bold; margin-top: 20px; margin-bottom: 16px;">
    {lang invited_by=$sender->getDisplayName() project_name=$context->getName() language=$language}:invited_by added you to the project ":project_name"{/lang}
</h1>

<p><a href="{$context->getViewUrl()}">{lang language=$language}Click here to join the project{/lang}</a></p>

<p>{lang language=$language}New to ActiveCollab? <a href="https://help.activecollab.com/">Visit this page</a> to find out more.{/lang}</p>
