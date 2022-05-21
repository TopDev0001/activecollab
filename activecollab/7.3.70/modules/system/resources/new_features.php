<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Module\System\Utils\NewFeatures\CallToAction\ExternalPage;
use ActiveCollab\Module\System\Utils\NewFeatures\CallToAction\InternalPage;
use ActiveCollab\Module\System\Utils\NewFeatures\CallToAction\PersonalizedInternalPage;
use ActiveCollab\Module\System\Utils\NewFeatures\NewFeatureAnnouncement;
use ActiveCollab\Module\System\Utils\NewFeatures\NewFeatureAnnouncementInterface;

const FEATURE_VISIBILITY_MANAGER = 'manager';

return [
    new NewFeatureAnnouncement(
        lang('Apply Templates'),
        lang('Add templates to your existing projects and automate all your processes. Create smaller templates that you can use as pieces of a puzzle for all your projects.'),
        new DateValue('2022-02-28'),
        new InternalPage(lang('Go to Projects'), 'projects'),
        function (User $user) {
            return $user->isPowerUser();
        }
    ),
    new NewFeatureAnnouncement(
        lang('In-App Chat on Mobile'),
        lang('Communication with your team using one app, wherever you are, on any device.'),
        new DateValue('2021-12-28'),
        new ExternalPage(
            lang('Learn More'),
            'https://activecollab.com/blog/product/in-app-chat-mobile'
        ),
        function (User $user) {
            return $user->isMember();
        }
    ),
    new NewFeatureAnnouncement(
        lang('Filtering and Sorting Completed Projects'),
        lang('Filter out your completed projects by client, category, label, or project lead. You can also sort them out by their completion date or storage space.'),
        new DateValue('2021-11-15'),
        new InternalPage(lang('Go to Projects'), 'projects/archive'),
    ),
    new NewFeatureAnnouncement(
        lang('Projects Page Makeover'),
        lang('Group projects, use new types of sorting, and filter by multiple criteria of the same type.'),
        new DateValue('2021-10-13'),
        new InternalPage(lang('Go to Projects'), 'projects'),
    ),
    new NewFeatureAnnouncement(
        lang('In-App Chat'),
        lang('Send a direct message to your colleagues, or entire teams, without leaving ActiveCollab.'),
        new DateValue('2021-08-30'),
        new ExternalPage(
            lang('Learn More'),
            'https://activecollab.com/blog/product/say-hello-to-our-in-app-chat'
        ),
        function (User $user) {
            return $user->isMember();
        }
    ),
    new NewFeatureAnnouncement(
        lang('Workload for Past Months'),
        lang("Review your team's workload for up to two months in the past."),
        new DateValue('2021-07-13'),
        new InternalPage(lang('Go to Workload'), 'workload'),
        function (User $user) {
            return $user->isPowerUser();
        }
    ),
    new NewFeatureAnnouncement(
        lang('New Mobile App'),
        lang('Download the new Mobile App and keep the ActiveCollab essentials in your pocket!'),
        new DateValue('2021-06-24'),
        new ExternalPage(
            lang('Go to Apps'),
            'https://activecollab.com/apps'
        )
    ),
    new NewFeatureAnnouncement(
        lang('Default Visibility for Clients'),
        lang("Now you can set your project's content to be 'Hidden from Clients' by default."),
        new DateValue('2021-04-19'),
        new InternalPage(lang('Go to Settings'), 'system-settings/project'),
        function (User $user) {
            return $user->isOwner();
        }
    ),
    new NewFeatureAnnouncement(
        lang('Task Dependencies'),
        lang('Search for an existing task through a sorted list or create a new task on the spot.'),
        new DateValue('2021-03-29'),
        new InternalPage(lang('Go to Projects'), 'projects'),
    ),
    new NewFeatureAnnouncement(
        lang('New Mention Types'),
        lang('Easily mention a whole team, everyone on a project, task assignee, project leader, and more.'),
        new DateValue('2021-03-01'),
        new ExternalPage(
            lang('Learn More'),
            'https://activecollab.com/help/books/activity/mention'
        )
    ),
    new NewFeatureAnnouncement(
        lang('Quick Options for Tasks'),
        lang('Three-dot menu lets you update tasks without opening them. Now available for task cards in the column view as well!'),
        new DateValue('2021-02-22'),
        new InternalPage(lang('Go to Projects'), 'projects'),
    ),
    new NewFeatureAnnouncement(
        lang('Activity Navigation'),
        lang('Browse activities by day, week, or month.'),
        new DateValue('2021-01-06'),
        new InternalPage(lang('Go to Activity'), 'activity'),
    ),
    new NewFeatureAnnouncement(
        sprintf('%s (%s)', lang('Browser Notifications'), 'Beta'),
        lang('Receive notifications on your desktop as soon as updates happen.'),
        new DateValue('2020-12-16'),
        new InternalPage(lang('Go to Settings'), 'settings'),
        function (User $user) {
            return $user->isMember();
        },
        [
            NewFeatureAnnouncementInterface::CHANNEL_CLOUD,
        ]
    ),
    new NewFeatureAnnouncement(
        lang('My Work Makeover'),
        lang("Everything's neatly tucked in tabs, collapse projects and lists, Availability tab added, My Work for Client+, and more."),
        new DateValue('2020-11-24'),
        new InternalPage(lang('Go to My Work'), 'my-work'),
        function (User $user) {
            return $user->isMember() || $user->isPowerClient();
        },
    ),
    new NewFeatureAnnouncement(
        lang('Group Tasks by Date'),
        lang('Everyone get the option to group tasks assigned to them by the due date. This should significantly improve your personal day-to-day organization.'),
        new DateValue('2020-11-02'),
        new InternalPage(lang('Go to My Work'), 'my-work'),
        function (User $user) {
            return $user->isMember() || $user->isPowerClient();
        },
    ),
    new NewFeatureAnnouncement(
        lang('QR Code for Invoices'),
        lang('Now you can add a QR code to your invoices.'),
        new DateValue('2020-10-30'),
        new InternalPage(lang('Go to Invoices'), 'invoices'),
        function (User $user) {
            return $user->isFinancialManager();
        },
    ),
    new NewFeatureAnnouncement(
        lang('My Tasks'),
        lang('Group your tasks by their due date. View the changes made to tasks in real-time.'),
        new DateValue('2020-10-19'),
        new InternalPage(lang('Go to My Work'), 'my-work'),
        function (User $user) {
            return $user->isMember();
        },
    ),
    new NewFeatureAnnouncement(
        sprintf('%s (%s)', lang('My Work'), 'Beta'),
        lang('Collapse and expand your tasks by project and task list.'),
        new DateValue('2020-10-06'),
        new InternalPage(lang('Go to My Work'), 'my-work'),
        function (User $user) {
            return $user->isMember();
        },
        [
            NewFeatureAnnouncementInterface::CHANNEL_CLOUD,
        ]
    ),
    new NewFeatureAnnouncement(
        lang('Xero Upgrade'),
        lang('Xero integration uses their latest authorization system.'),
        new DateValue('2020-08-27'),
        new InternalPage(lang('Go to Xero Add-On'), 'integrations/xero'),
        function (User $user) {
            return $user->isPowerUser();
        }
    ),
    new NewFeatureAnnouncement(
        lang('Budget Alerts'),
        lang('Set up an alert, and the system will notify you when the specified percentages of the budget’s spent amount are reached.'),
        new DateValue('2020-07-29'),
        new InternalPage(lang('Go to Projects'), 'projects'),
        function (User $user) {
            return $user->isFinancialManager();
        }
    ),
    new NewFeatureAnnouncement(
        lang('Availability visible in the Date Picker'),
        lang('When setting up a due date for a task, weekends and holidays can’t be selected. If the assignee took days off, the period can be selected but will be marked red.'),
        new DateValue('2020-07-29'),
        new InternalPage(lang('Go to Projects'), 'projects'),
        function (User $user) {
            return $user->isFinancialManager();
        }
    ),
    new NewFeatureAnnouncement(
        lang('New Column View'),
        lang('What’s new: faster loading time, real-time changes, and a Completed Tasks column. Drag and drop your tasks to complete or reopen them.'),
        new DateValue('2020-06-26'),
        new InternalPage(lang('Go to Projects'), 'projects'),
    ),
    new NewFeatureAnnouncement(
        lang('Project Stopwatch'),
        lang('Track your time directly on a project as a whole, rather than on specific tasks.'),
        new DateValue('2020-06-06'),
        new InternalPage(lang('Go to Projects'), 'projects'),
        function (User $user) {
            return $user->isMember();
        }
    ),
    new NewFeatureAnnouncement(
        lang('Target Budget'),
        lang('Now available for the Time and Expenses type of project. Set a Target Budget and keep an eye on how much of it is spent.'),
        new DateValue('2020-05-27'),
        new InternalPage(lang('Go to Projects'), 'projects'),
        function (User $user) {
            return $user->isFinancialManager();
        }
    ),
    new NewFeatureAnnouncement(
        lang('Fixed Price and Non-Billable Projects'),
        lang('Charge only the price agreed upon while using time and expenses to keep an eye on project profitability. Or mark projects as Non-Billable and track costs only.'),
        new DateValue('2020-04-29'),
        new InternalPage(lang('Go to Projects'), 'projects'),
        function (User $user) {
            return $user->isPowerUser();
        }
    ),
    new NewFeatureAnnouncement(
        lang('Duplicate Task Lists'),
        lang('Need to do the same tasks again? You can now duplicate your task lists in just a couple of clicks.'),
        new DateValue('2020-04-23'),
        new InternalPage(lang('Go to Projects'), 'projects'),
        function (User $user) {
            return $user->isMember();
        }
    ),
    new NewFeatureAnnouncement(
        lang('Stopwatches on Timesheet'),
        lang('Start and pause stopwatches directly on your timesheet.'),
        new DateValue('2020-04-13'),
        new InternalPage(lang('Go to My Work'), 'my-work'),
        function (User $user) {
            return $user->isMember();
        }
    ),
    new NewFeatureAnnouncement(
        lang('Mark Projects and Tasks as Billable'),
        lang("Decide what's billable and what's non-billable on a project or task level. Make sure that billable hours don't slip through your fingers."),
        new DateValue('2020-04-09'),
        new InternalPage(lang('Go to Projects'), 'projects'),
        function (User $user) {
            return $user->isPowerUser();
        }
    ),
    new NewFeatureAnnouncement(
        lang('Personal Timesheet'),
        lang('New version of personal timesheet will clearly display all your tasks for the current week and enable you to easily enter time records for those tasks.'),
        new DateValue('2020-02-28'),
        new InternalPage(lang('Go to My Work'), 'my-work'),
        function (User $user) {
            return $user->isMember();
        }
    ),
    new NewFeatureAnnouncement(
        lang('Project Cost'),
        lang('Make sure you’re always aware of your project costs! Now they’re neatly summed up in the Project Info.'),
        new DateValue('2020-01-27'),
        new InternalPage(lang('Go to Projects'), 'projects'),
        function (User $user) {
            return $user->isFinancialManager();
        }
    ),
    new NewFeatureAnnouncement(
        lang('Team Timesheet'),
        lang('The Team timesheet offers a quick overview of where your team’s time has been invested.'),
        new DateValue('2020-01-27'),
        new InternalPage(lang('Go to Team Timesheet'), 'timesheet'),
        function (User $user) {
            return $user->isPowerUser();
        }
    ),
    new NewFeatureAnnouncement(
        lang('Collapse Sidebar'),
        lang('Click on the arrow icon in the lower left corner to collapse the sidebar and gain even more space for your work!'),
        new DateValue('2020-01-13')
    ),
    new NewFeatureAnnouncement(
        sprintf('%s (%s)', lang('Team Timesheet'), 'Beta'),
        sprintf(
            '%s %s',
            lang('The Team timesheet offers a quick overview of where your team’s time has been invested.'),
            lang('This is the first beta build. Next up: color coding and data drill down!')
        ),
        new DateValue('2020-01-10'),
        new InternalPage(lang('Go to Team Timesheet'), 'timesheet'),
        function (User $user) {
            return $user->isPowerUser();
        },
        [
            NewFeatureAnnouncementInterface::CHANNEL_CLOUD,
        ]
    ),
    new NewFeatureAnnouncement(
        lang('Brand New Time Report'),
        lang('The Time report went through a major reconstruction! A couple of new tricks, such as data grouping and summation, have been added. Now you can bookmark reports that you use often, or share them with other team members.'),
        new DateValue('2019-12-30'),
        new InternalPage(lang('Go to Time Report'), 'reports/time/project'),
        function (User $user) {
            return $user->isPowerUser();
        }
    ),
    new NewFeatureAnnouncement(
        lang('Convert Projects to Templates'),
        lang('Save your projects and use them as templates. Spend your time on work that really matters!'),
        new DateValue('2019-11-25'),
        new InternalPage(lang('Go to Projects'), 'projects'),
        function (User $user) {
            return $user->isPowerUser();
        }
    ),
    new NewFeatureAnnouncement(
        lang('QuickBooks Upgrade'),
        lang('QuickBooks integration uses their latest authorization system.'),
        new DateValue('2019-11-19'),
        new InternalPage(lang('Go to QuickBooks Add-On'), 'integrations/quickbooks'),
        function (User $user) {
            return $user->isPowerUser();
        }
    ),
    new NewFeatureAnnouncement(
        lang('Timeline Improvements'),
        lang('Project timelines show full dependency chains in open task lists and can be zoomed out to provide better overview.'),
        new DateValue('2019-11-18'),
        new InternalPage(lang('Go to Projects'), 'projects')
    ),
    new NewFeatureAnnouncement(
        lang('Workload for Future Months'),
        lang("Plan and manage your team's workload up to a full year in the future."),
        new DateValue('2019-11-05'),
        new InternalPage(lang('Go to Workload'), 'workload'),
        function (User $user) {
            return $user->isPowerUser();
        }
    ),
    new NewFeatureAnnouncement(
        lang('Completed Tasks in Lists'),
        lang("Your completed tasks are now neatly tucked below each task list. Three of the most recent ones are shown by default, giving a better overview of the work progress you're making."),
        new DateValue('2019-10-10'),
        new InternalPage(lang('Go to Projects'), 'projects')
    ),
    new NewFeatureAnnouncement(
        lang('Stopwatches'),
        lang('Track time directly in ActiveCollab. No separate app needed.'),
        new DateValue('2019-09-19'),
        new ExternalPage(lang('Learn More'), 'https://activecollab.com/blog/product/activecollab-stopwatch')
    ),

    // Owner should be redirected to bundle promo page.
    new NewFeatureAnnouncement(
        lang('Workload Management'),
        lang("A birds-eye view of all your projects and real-time status of your team's daily availability and capacity."),
        new DateValue('2019-09-02'),
        new InternalPage(lang('Learn More'), 'bundles/get-paid'),
        function (User $user) {
            return $user->isOwner();
        }
    ),

    // Member+ should be redirected to Workload page.
    new NewFeatureAnnouncement(
        lang('Workload Management'),
        lang("A birds-eye view of all your projects and real-time status of your team's daily availability and capacity."),
        new DateValue('2019-09-02'),
        new InternalPage(lang('Learn More'), 'workload'),
        function (User $user) {
            return $user->isPowerUser() && !$user->isOwner();
        }
    ),
    new NewFeatureAnnouncement(
        lang('Availability'),
        lang("You and your team can now add availability records. This way you'll always know who's available or not, when, and for how long."),
        new DateValue('2019-09-02'),
        new PersonalizedInternalPage(lang('Go to Profile'), 'users/:user_id'),
        function (User $user) {
            return $user->isMember();
        }
    ),
    new NewFeatureAnnouncement(
        sprintf('%s (%s)', lang('Stopwatches'), 'Beta'),
        lang('Track time directly in ActiveCollab. No separate app needed.'),
        new DateValue('2019-08-16'),
        new ExternalPage(
            lang('Go to Roadmap'),
            'https://activecollab.com/roadmap'
        ),
        function (User $user) {
            return $user->isOwner();
        },
        [
            NewFeatureAnnouncementInterface::CHANNEL_CLOUD,
        ]
    ),
    new NewFeatureAnnouncement(
        lang('Non-Working Days'),
        lang('Specify company-wide non-working days, like national holidays, company retreats etc. ActiveCollab will use that information to help out with scheduling.'),
        new DateValue('2019-08-01'),
        new InternalPage(lang('Go to Workday Settings'), 'system-settings/workday'),
        function (User $user) {
            return $user->isOwner();
        }
    ),
    new NewFeatureAnnouncement(
        sprintf('%s (%s)', lang('Workload Management'), 'Beta'),
        lang("A birds-eye view of all your projects and real-time status of your team's daily availability and capacity."),
        new DateValue('2019-07-16'),
        new InternalPage(lang('Learn More'), 'workload'),
        function (User $user) {
            return $user->isOwner();
        },
        [
            NewFeatureAnnouncementInterface::CHANNEL_CLOUD,
        ]
    ),
    new NewFeatureAnnouncement(
        lang('Task Dependencies in Templates'),
        lang('Automate your workflow even further with task dependencies in project templates.'),
        new DateValue('2019-06-05'),
        new InternalPage(lang('Go to Templates'), 'project-templates')
    ),
    new NewFeatureAnnouncement(
        lang('Time Estimates in Templates'),
        lang('Specify how much time each task should take in project templates, and have that value set when projects are created from templates.'),
        new DateValue('2019-05-20'),
        new InternalPage(lang('Go to Templates'), 'project-templates')
    ),
    new NewFeatureAnnouncement(
        lang('Timeline Export'),
        lang('Export the timeline into PDF or PNG so you can share, print, use it in presentations, etc.'),
        new DateValue('2019-05-10'),
        new InternalPage(lang('Go to Projects'), 'projects')
    ),
    new NewFeatureAnnouncement(
        lang('Improved Move/Copy Permissions'),
        lang('More people can move items between projects, which reduces friction for teams that use multi-project workflows.'),
        new DateValue('2019-05-10'),
        new InternalPage(lang('Go to Projects'), 'projects')
    ),
    new NewFeatureAnnouncement(
        lang('Additional Recurring Task Intervals'),
        lang("You'll have more control over task scheduling and planning with additional recurring task intervals."),
        new DateValue('2019-04-22'),
        new InternalPage(lang('Go to Projects'), 'projects')
    ),
    new NewFeatureAnnouncement(
        lang('Neon Theme'),
        lang('Give your eyes a bit of relief by choosing the Neon Theme.'),
        new DateValue('2019-02-27'),
        new InternalPage(lang('Change Theme'), 'profile')
    ),
    new NewFeatureAnnouncement(
        lang('Duplicate Project Templates'),
        lang('Duplicate existing project templates without having to start all over if you just need to make minor adjustments.'),
        new DateValue('2019-02-26'),
        new InternalPage(lang('Go to Templates'), 'project-templates')
    ),
    new NewFeatureAnnouncement(
        lang('Automatic Rescheduling'),
        lang('When one task needs to be rescheduled, you can also automatically reschedule all dependent activities.'),
        new DateValue('2019-01-10'),
        new InternalPage(lang('Go to Projects'), 'projects')
    ),
    new NewFeatureAnnouncement(
        lang('Task Dependencies'),
        lang('Set dependencies between tasks to clearly communicate execution order.'),
        new DateValue('2019-01-10'),
        new InternalPage(lang('Go to Projects'), 'projects')
    ),
    new NewFeatureAnnouncement(
        lang('Themes & Customization'),
        lang('Customization options evolved to the next level, with several new themes to choose from and complement your work style preferences.'),
        new DateValue('2019-01-10'),
        new InternalPage(lang('Change Theme'), 'profile')
    ),
    new NewFeatureAnnouncement(
        lang('Improved Attachment Navigation'),
        lang('Navigate through your attachments by using the left and right arrow keys, or by clicking the left and right icons with your mouse.'),
        new DateValue('2018-10-05'),
        new InternalPage(lang('Go to Projects'), 'projects')
    ),
    new NewFeatureAnnouncement(
        lang('Live Comments'),
        lang('See when your colleagues and clients are typing, and have their comments appear instantly when they are sent.'),
        new DateValue('2018-09-24'),
        new InternalPage(lang('Go to Projects'), 'projects'),
        null,
        [
            NewFeatureAnnouncementInterface::CHANNEL_CLOUD,
        ]
    ),
    new NewFeatureAnnouncement(
        lang('Task Input Protection'),
        lang('System warns users if they are closing non-empty task forms, making accidental input loss less likely to happen.'),
        new DateValue('2018-09-24'),
        new InternalPage(lang('Go to Projects'), 'projects')
    ),
    new NewFeatureAnnouncement(
        lang('More Languages'),
        lang('ActiveCollab is now available in 20 languages! Latest additions are Japanese, Romanian and Slovak.'),
        new DateValue('2018-09-21'),
        new InternalPage(lang('Change Language'), 'profile')
    ),
    new NewFeatureAnnouncement(
        lang('Default Job Type'),
        lang('ActiveCollab remembers which job type people are using to track time, and selects it automatically.'),
        new DateValue('2018-08-03'),
        new InternalPage(lang('Go to Projects'), 'projects'),
        function (User $user) {
            return $user->isMember();
        }
    ),
    new NewFeatureAnnouncement(
        lang('Lithuanian Language'),
        lang('Use ActiveCollab in Lithuanian.'),
        new DateValue('2018-07-29'),
        new InternalPage(lang('Change Language'), 'profile')
    ),
    new NewFeatureAnnouncement(
        lang('Comment Reactions'),
        lang("See something you like? Punch the like button. Don't like what you see, give it a thumb down."),
        new DateValue('2018-06-12'),
        new InternalPage(lang('Go to Projects'), 'projects')
    ),
    new NewFeatureAnnouncement(
        lang('Since Last Visit'),
        lang('Never miss comments that have been posted since your last visit to a task or a discussion.'),
        new DateValue('2018-05-30'),
        new ExternalPage(
            lang('Learn More'),
            'https://activecollab.com/blog/product/since-your-last-visit'
        )
    ),
    new NewFeatureAnnouncement(
        lang('Croatian Language'),
        lang('Use ActiveCollab in Croatian.'),
        new DateValue('2018-03-31'),
        new InternalPage(lang('Change Language'), 'profile')
    ),
    new NewFeatureAnnouncement(
        lang('ActiveCollab Subscription Page'),
        lang('Easily access your ActiveCollab subscription details in one place, including all you invoices.'),
        new DateValue('2018-03-19'),
        new InternalPage(lang('Go to Subscription'), 'subscription'),
        function (User $user) {
            return $user->isOwner();
        },
        [
            NewFeatureAnnouncementInterface::CHANNEL_CLOUD,
        ]
    ),
    new NewFeatureAnnouncement(
        lang('Desktop App'),
        lang('Run ActiveCollab as an app on your Mac or Windows computer.'),
        new DateValue('2018-02-23'),
        new InternalPage(lang('Download the App'), 'integrations/desktop-app'),
        function (User $user) {
            return $user->isMember();
        }
    ),
    new NewFeatureAnnouncement(
        lang('Sample Projects'),
        lang('There are many ways to use ActiveCollab. Check out Sample Projects to get inspired.'),
        new DateValue('2018-01-29'),
        new InternalPage(lang('Create a Sample Project'), 'integrations/sample-projects'),
        function (User $user) {
            return $user->isMember();
        }
    ),
    new NewFeatureAnnouncement(
        lang('Better Search'),
        lang('Search is faster, and offers more filtering and sorting options.'),
        new DateValue('2017-12-26'),
        new InternalPage(lang('Go to Search'), 'search')
    ),
    new NewFeatureAnnouncement(
        lang('Hungarian Language'),
        lang('Use ActiveCollab in Hungarian.'),
        new DateValue('2017-12-16'),
        new InternalPage(lang('Change Language'), 'profile')
    ),
    new NewFeatureAnnouncement(
        lang('Performance Improvements'),
        lang('Navigate ActiveCollab faster than ever before thanks to frontend, backend and infrastructure optimizations.'),
        new DateValue('2017-11-07'),
        new InternalPage(lang('Go to My Work'), 'my-work'),
        function (User $user) {
            return $user->isMember();
        }
    ),
    new NewFeatureAnnouncement(
        lang('Copy Images from Clipboard'),
        lang('Copy images and paste them directly into a text field when you are adding tasks, writing comments, etc.'),
        new DateValue('2017-10-04'),
        new InternalPage(lang('Go to Projects'), 'projects')
    ),
    new NewFeatureAnnouncement(
        lang('Zapier Integration'),
        lang('Connect ActiveCollab to more than 750 different apps using Zapier.'),
        new DateValue('2017-03-13'),
        new InternalPage(lang('Go to the Add-On'), 'integrations/zapier'),
        function (User $user) {
            return $user->isOwner();
        }
    ),
    new NewFeatureAnnouncement(
        lang('Column Sort'),
        lang('Sort column values in a report by clicking on the column name.'),
        new DateValue('2017-03-13'),
        new InternalPage(lang('Go to Reports'), 'reports'),
        function (User $user) {
            return $user->isOwner() || ($user->isMember() && $user->isManager());
        }
    ),
    new NewFeatureAnnouncement(
        lang('Even More Languages'),
        lang('Use ActiveCollab in Czech, Italian, Portuguese, Serbian, or Turkish.'),
        new DateValue('2017-01-17'),
        new InternalPage(lang('Change Language'), 'profile')
    ),
    new NewFeatureAnnouncement(
        lang('More Languages'),
        lang('Use ActiveCollab in Russian or Swedish (in addition to Dutch, English, French, German, Polish, and Spanish).'),
        new DateValue('2016-10-04'),
        new InternalPage(lang('Change Language'), 'profile')
    ),
    new NewFeatureAnnouncement(
        lang('Email Notification Settings'),
        lang('Control how much email you want to receive from ActiveCollab.'),
        new DateValue('2016-09-07'),
        new InternalPage(lang('Go to Preferences'), 'settings')
    ),
    new NewFeatureAnnouncement(
        lang('Google Drive Integration'),
        lang('Share your Google Drive documents as attachments in ActiveCollab.'),
        new DateValue('2016-09-07'),
        new InternalPage(lang('Go to the Add-On'), 'integrations/google-drive'),
        function (User $user) {
            return $user->isOwner();
        }
    ),
    new NewFeatureAnnouncement(
        lang('Dropbox Integration'),
        lang('Share Dropbox files on projects with your team.'),
        new DateValue('2016-09-07'),
        new InternalPage(lang('Go to the Add-On'), 'integrations/dropbox'),
        function (User $user) {
            return $user->isOwner();
        }
    ),
    new NewFeatureAnnouncement(
        lang('Advanced Mode for Time Report'),
        lang('Advanced time search and filtering.'),
        new DateValue('2016-08-16'),
        new InternalPage(lang('Go to the Report'), 'reports/time-tracking'),
        function (User $user) {
            return $user->isOwner() || ($user->isMember() && $user->isPowerUser());
        }
    ),
    new NewFeatureAnnouncement(
        lang('Projects Report'),
        lang('List all the projects that meet certain criteria.'),
        new DateValue('2016-08-16'),
        new InternalPage(lang('Go to the Report'), 'reports/projects'),
        function (User $user) {
            return $user->isOwner() || ($user->isMember() && $user->isPowerUser());
        }
    ),
    new NewFeatureAnnouncement(
        lang('Invoices Report'),
        lang('Find all the invoices you need that meet certain criteria.'),
        new DateValue('2016-08-16'),
        new InternalPage(lang('Go to the Report'), 'reports/invoices'),
        function (User $user) {
            return $user->isOwner() || ($user->isMember() && $user->isFinancialManager());
        }
    ),
    new NewFeatureAnnouncement(
        lang('Xero Integration'),
        lang('Create invoices from billable time and expenses in ActiveCollab, and then send them to your Xero account for further processing.'),
        new DateValue('2016-08-16'),
        new InternalPage(lang('Go to the Add-On'), 'integrations/xero'),
        function (User $user) {
            return $user->isOwner();
        }
    ),
    new NewFeatureAnnouncement(
        lang('iCalendar Projects Feed'),
        lang('Import project task and task list dates into your favorite calendar app so you can see what is coming up.'),
        new DateValue('2016-08-07'),
        new ExternalPage(
            lang('Learn More'),
            'https://help.activecollab.com/books/activity/calendar.html#s-exporting-calendar-feeds'
        ),
        function (User $user) {
            return $user->isMember(true);
        }
    ),
    new NewFeatureAnnouncement(
        lang('Hubstaff Integration'),
        lang('Track time on ActiveCollab tasks using the Hubstaff timer and keep time logs synced across both systems.'),
        new DateValue('2016-07-04'),
        new InternalPage(lang('Go to the Add-On'), 'integrations/hubstaff'),
        function (User $user) {
            return $user->isOwner();
        }
    ),
    new NewFeatureAnnouncement(
        lang('Team Timeline'),
        lang('See how much work each team member has on a Gantt-like timeline so you can plan and manage their workload better.'),
        new DateValue('2016-07-04'),
        new InternalPage(lang('Go to the Report'), 'reports/team-timeline'),
        function (User $user) {
            return $user->isOwner() || ($user->isMember() && $user->isPowerUser());
        }
    ),
    new NewFeatureAnnouncement(
        lang('Projects Timeline'),
        lang('See all your projects on a Gantt-like timeline so you can plan and manage them better.'),
        new DateValue('2016-07-04'),
        new InternalPage(lang('Go to the Report'), 'reports/projects-timeline'),
        function (User $user) {
            return $user->isOwner() || ($user->isMember() && $user->isPowerUser());
        }
    ),
    new NewFeatureAnnouncement(
        lang('More Languages'),
        lang('Use ActiveCollab in Dutch, Italian, and Polish (in addition to English, French, German, and Spanish).'),
        new DateValue('2016-07-04'),
        new InternalPage(lang('Change Language on the Profile Page'), 'profile')
    ),
    new NewFeatureAnnouncement(
        lang('Recurring Tasks'),
        lang('Save time by letting ActiveCollab automatically create recurring tasks every day, week, or month.'),
        new DateValue('2016-05-19'),
        new ExternalPage(
            lang('Learn More'),
            'https://blog.activecollab.com/product/2016/05/19/recurring-tasks.html'
        ),
        function (User $user) {
            return $user->isMember(true);
        }
    ),
    new NewFeatureAnnouncement(
        lang('Exact Time and Date on Comments'),
        lang('Hover the mouse over when a comment was posted, and the exact time and date will appear in a few seconds.'),
        new DateValue('2016-05-19'),
        new InternalPage(lang('Go to Projects'), 'projects')
    ),
    new NewFeatureAnnouncement(
        lang('Quick Jump'),
        lang('Instantly open any part of ActiveCollab or project by pressing CMD+K (Mac) or CTRL+K (Windows).'),
        new DateValue('2016-05-19'),
        new ExternalPage(
            lang('Learn Other Shortcuts'),
            'https://help.activecollab.com/books/my-active-collab/keyboard-shortcuts.html'
        )
    ),
    new NewFeatureAnnouncement(
        lang('Client+'),
        lang('Give clients a Client+ role so they can create and assign tasks as well as be an assignee.'),
        new DateValue('2016-03-30'),
        new InternalPage(lang('Go to the Add-On'), 'integrations/client-plus'),
        function (User $user) {
            return $user->isOwner();
        }
    ),
    new NewFeatureAnnouncement(
        lang('Slack Integration'),
        lang('Connect ActiveCollab projects to a Slack channel to receive notifications whenever something happens on your projects.'),
        new DateValue('2016-03-30'),
        new InternalPage(lang('Go to the Integration'), 'integrations/slack'),
        function (User $user) {
            return $user->isOwner();
        }
    ),
    new NewFeatureAnnouncement(
        lang('Custom Date Ranges in Time Report'),
        lang('Pick a custom date range in the Time, Expense, or Payments report.'),
        new DateValue('2016-03-30'),
        new InternalPage(lang('Go to the Reports'), 'reports'),
        function (User $user) {
            return $user->isOwner() || ($user->isMember() && $user->isPowerUser());
        }
    ),
    new NewFeatureAnnouncement(
        lang('QuickBooks Integration'),
        lang('Create invoices from billable time and expenses in ActiveCollab, and then send them to your QuickBooks account for further processing.'),
        new DateValue('2016-02-03'),
        new InternalPage(lang('Go to the Integration'), 'integrations/quickbooks'),
        function (User $user) {
            return $user->isOwner();
        }
    ),
    new NewFeatureAnnouncement(
        lang('Languages'),
        lang('Use ActiveCollab in English, French, German, or Spanish.'),
        new DateValue('2016-02-03'),
        new InternalPage(lang('Change Language on the Profile Page'), 'profile')
    ),
    new NewFeatureAnnouncement(
        lang('Trello Importer'),
        lang('Copy your Trello projects into ActiveCollab.'),
        new DateValue('2016-02-03'),
        new InternalPage(lang('Go to the Integration'), 'integrations/trello-importer'),
        function (User $user) {
            return $user->isOwner();
        }
    ),
    new NewFeatureAnnouncement(
        lang('Basecamp Importer'),
        lang('Copy your Basecamp projects into ActiveCollab.'),
        new DateValue('2015-11-13'),
        new InternalPage(lang('Go to the Integration'), 'integrations/basecamp-importer'),
        function (User $user) {
            return $user->isOwner();
        }
    ),
    new NewFeatureAnnouncement(
        lang('Webhooks'),
        lang('Notify 3rd party services about what’s happening in ActiveCollab. Webhooks lets you integrate with existing apps for custom workflows.'),
        new DateValue('2015-11-13'),
        new InternalPage(lang('Go to the Integration'), 'integrations/webhooks'),
        function (User $user) {
            return $user->isOwner();
        }
    ),
    new NewFeatureAnnouncement(
        lang('Mail to Project'),
        lang('Create tasks and post comments directly from your email. When you get an email notification, just send a reply to post a comment. Send emails to a project email address to create tasks.'),
        new DateValue('2015-11-13'),
        new ExternalPage(
            lang('See How It Works'),
            'https://help.activecollab.com/books/projects/email-to-projects.html'
        )
    ),
    new NewFeatureAnnouncement(
        lang('Timeline View'),
        lang('Manage and schedule all your tasks in a Gantt-like timeline.'),
        new DateValue('2015-11-13'),
        new ExternalPage(
            lang('See How It Works'),
            'https://help.activecollab.com/books/projects/tasks.html#s-using-task-views'
        )
    ),
    new NewFeatureAnnouncement(
        lang('Column View'),
        lang('See your tasks as cards on a Kanban board and move them across columns.'),
        new DateValue('2015-11-13'),
        new ExternalPage(
            lang('See How It Works'),
            'https://help.activecollab.com/books/projects/tasks.html#s-using-task-views'
        )
    ),
];
