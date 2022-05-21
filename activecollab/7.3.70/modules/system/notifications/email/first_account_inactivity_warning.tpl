{$subject}
================================================================================
{notification_logo}

{if $payload}
    <p>{lang language=$language}Greetings!{/lang},</p>
    <p>{lang language=$language}Hope your business is going well.{/lang}</p>
    <p>{lang language=$language}We noticed your activity on ActiveCollab has dropped in the past 30 days. We are obligated to inform you that after 90 days of inactivity, accounts get deleted from the system. If there is anything we can assist you with regarding the app, feel free to reach out to our support teams via chat or email, they will gladly answer any questions you have for us.{/lang}</p>
    <p>{lang language=$language}You can login to your account on this <a href="{$payload.link}">link</a>.{/lang}</p>
    <h3>{lang language=$language}What happens with my data?{/lang}</h3>
    <p>{lang language=$language}Since your account has had no registered activity in the past 30 days, we decided to inform you that after 90 days of inactivity, ActiveCollab accounts will be deleted. After 30, 60, and 83 days, the system will notify the user that the account is going to be deleted. If the account remains inactive for 90 days in a row the user will receive a JSON export of the data added to the ActiveCollab account once the account gets deleted.{/lang}</p>
    <p>{lang language=$language}To see the full disclosure, click <a href="{$payload.help_link}">here</a>{/lang}</p>
    <h3>{lang language=$language}What can I do to prevent the removal of my account?{/lang}</h3>
    <p>{lang language=$language}To prevent the removal of the account, you can continue using your ActiveCollab account by logging in and performing any activity such as creating a project, task, inviting a user, posting a comment, etc.{/lang}</p>
    <p>{lang language=$language}You can log in to your account by clicking on this <a href="{$payload.link}">link</a>.{/lang}</p>
    <p>{lang language=$language}ActiveCollab Team{/lang}</p>
{/if}
