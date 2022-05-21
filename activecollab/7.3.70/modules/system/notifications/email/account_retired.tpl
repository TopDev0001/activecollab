{lang language=$language}Your account is retired now{/lang}
================================================================================
{notification_logo}

<p>{lang name=$recipient->getForcedFirstName() language=$language}Hi :name{/lang},</p>
<p>{lang export_to_email_address=$export_to_email_address language=$language}Your account is retired now. This means that it is no longer accessible to anyone. A copy of your data will be sent to :export_to_email_address in the next 48 hours.{/lang}</p>
<p>{lang language=$language account_id=$retired_account_id}If you think that account removal happened by mistake, or have any questions,  please contact <a href="mailto:support@activecollab.com?subject=Retired account :account_id">support@activecollab.com</a>.{/lang}</p>
<p>{lang language=$language}ActiveCollab Team{/lang}</p>
