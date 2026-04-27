# Dashboard announcements block

`block_dashboardannouncements` is a Moodle dashboard block that lets authorised staff publish announcements to selected audiences and optionally queue notification or email delivery through cron.

## Features

- Shows up to 10 relevant published announcements in the dashboard block
- Provides a full `View all` page for announcements visible to the current user
- Supports one target mode per announcement:
  - all users
  - users enrolled in courses within selected course categories
  - users in selected cohorts
  - users whose custom profile field matches a configured operator and value
- Queues optional delivery by Moodle notifications, email, or both
- Tracks admin-only management statistics:
  - target audience summary
  - first-send targeted snapshot count
  - unique successful notified count

## Supported Moodle versions

This plugin currently declares support for Moodle `4.0+` via `version.php` (`$plugin->requires = 2022041900`).

Manual verification against maintained Moodle versions is still required before publishing a release.

## Installation

1. Place the plugin in `blocks/dashboardannouncements`.
2. Visit the site administration notifications page to complete installation.
3. Add the block to the dashboard or another supported page layout.
4. Grant `block/dashboardannouncements:manage` to the roles that should create and manage announcements.

No Composer or external build step is required.

## Configuration and usage

- Create and manage announcements at `/blocks/dashboardannouncements/manage.php`.
- Create or edit announcements with title, message, date window, target audience, and delivery mode.
- Run Moodle cron to process queued delivery jobs.

## Dependencies

This plugin depends only on Moodle core APIs.

It does not require any additional plugins or third-party subscription services.

## Privacy

The plugin stores announcement content, queue metadata, and per-user delivery log rows in Moodle database tables. A Privacy API metadata provider is included in `classes/privacy/provider.php`.

## Limitations

- Runtime verification has not been performed in this workspace because no local Moodle/PHP runtime is available here.
- Cross-database and automated precheck validation still need to be run by a human maintainer before publishing.

## Support

- Source repository URL: add your public repository URL before publishing
- Issue tracker URL: add your public issue tracker URL before publishing
- Documentation URL: add your Moodle Docs or public documentation URL before publishing

## Manual publishing checklist

Before submitting to the Moodle plugins directory, manually verify:

1. Installation and upgrade on supported Moodle versions
2. Cross-database behaviour on MySQL and PostgreSQL
3. Cron delivery processing for notification and email channels
4. Audience resolution for category, cohort, and profile field targeting
5. Access control for block visibility, management, and direct URL access
