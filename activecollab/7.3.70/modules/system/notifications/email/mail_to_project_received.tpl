{lang language=$language}Your Email Message has been Received{/lang}{if $matched_recipient_address !== $project->getMailToProjectEmail()} ({lang language=$language}Action Required{/lang}){/if}
================================================================================
{notification_logo}

<p>{lang language=$language}Your email message has been imported as <a href="{$context->getViewUrl()}">{$context->getName()}</a> {$context->getVerboseType(true, $language)} in <a href="{$project->getViewUrl()}">{$project->getName()}</a> project.{/lang}</p>

{if $matched_recipient_address !== $project->getMailToProjectEmail()}
    <p><b>Notice: Time to update you address book!</b></p>
    <p>As of January 1st 2022. we'll switch from @activecollab.com project addresses to @activecollab.email. Please send messages to {$project->getMailToProjectEmail()} to add tasks and discussions to this project. After January 1st 2022. messages sent to @activecollab.com project addresses will no longer be imported!</p>
{/if}
