# Dashboard announcements block

`block_dashboardannouncements` is a Moodle dashboard block that lets authorised staff publish announcements to selected audiences and optionally queue notification or email delivery through cron.

## Plugin directory metadata

### Short description (1-2 sentences)

Dashboard announcements is a Moodle block for publishing targeted announcements with optional queued messaging delivery. It provides end-user list/detail views plus manager create/edit/archive workflows.

### Long description

Dashboard announcements allows managers to create announcements with scheduling, audience targeting, optional attachments, and optional popup display at login. The plugin supports all-user, category, cohort, and user-field audience selection, queues delivery through Moodle messaging, and records delivery statistics for management review. The user interface includes dashboard cards, a full list view with filtering and pagination, and manager views for authoring and lifecycle control.

## Features

- Shows up to 5 relevant published announcements in the dashboard block
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
Moodle App support is implemented via plugin mobile handlers (`db/mobile.php`) with manual runtime validation pending.

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

### UI Refresh Notes (2026-04)

- End-user block, list, and detail pages now use a shared card-based presentation.
- Management and edit pages now use the same status badge and metadata treatment patterns.
- Create/edit fields are grouped into sections: Content, Schedule, Audience, Delivery, Attachment, and Record state.
- Empty states, date/metadata fallbacks, and attachment actions are rendered through shared helpers in `classes/local/presentation_helper.php`.
- Shared visual rules live in `styles.css` and are designed for desktop/mobile readability and keyboard-visible focus states.

### Moodle App Support Notes (2026-04)

- Comming Soon

## Dependencies

This plugin depends only on Moodle core APIs.

It does not require any additional plugins or third-party subscription services.

## Privacy

The plugin stores announcement content, queue metadata, and per-user delivery log rows in Moodle database tables. A Privacy API metadata provider is included in `classes/privacy/provider.php`.
