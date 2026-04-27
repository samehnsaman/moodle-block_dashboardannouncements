<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

$string['pluginname'] = 'Dashboard announcements';
$string['dashboardannouncements:addinstance'] = 'Add a new dashboard announcements block';
$string['dashboardannouncements:manage'] = 'Manage dashboard announcements';
$string['dashboardannouncements:myaddinstance'] = 'Add a new dashboard announcements block to the Dashboard';
$string['dashboardannouncements:view'] = 'View dashboard announcements';
$string['dashboardannouncements:viewall'] = 'View all dashboard announcements';
$string['messageprovider:announcement'] = 'Announcement notifications';

$string['title'] = 'Title';
$string['date'] = 'Date';
$string['message'] = 'Message';
$string['messagepreview'] = 'Message preview';
$string['status'] = 'Status';
$string['status:draft'] = 'Draft';
$string['status:published'] = 'Published';
$string['status:disabled'] = 'Disabled';
$string['status:archived'] = 'Archived';
$string['startdate'] = 'Start date';
$string['enddate'] = 'End date';
$string['targettype'] = 'Target audience type';
$string['targetconfig'] = 'Target audience configuration';
$string['targettype:all'] = 'All users';
$string['targettype:category'] = 'Course category audience';
$string['targettype:cohort'] = 'Cohort audience';
$string['targettype:field'] = 'User field audience';
$string['sendmode'] = 'Delivery mode';
$string['sendmode:none'] = 'Publish only';
$string['sendmode:message'] = 'Publish and notify';
$string['categoryids'] = 'Course categories';
$string['cohortids'] = 'Cohorts';
$string['fieldlookup'] = 'User field';
$string['fieldoperator'] = 'Filter operator';
$string['fieldmatchvalue'] = 'Filter value';
$string['fieldoperator:contains'] = 'Contains';
$string['fieldoperator:notcontains'] = 'Doesn\'t contain';
$string['fieldoperator:equal'] = 'Is equal to';
$string['fieldoperator:startswith'] = 'Starts with';
$string['fieldoperator:endswith'] = 'Ends with';
$string['fieldoperator:isempty'] = 'Is empty';
$string['fieldoperator:isnotempty'] = 'Is not empty';
$string['attachment'] = 'Attachment';
$string['attachmentdownload'] = 'Download attachment';
$string['noattachment'] = '-';
$string['search'] = 'Search';
$string['filter'] = 'Filter';
$string['datefrom'] = 'From date';
$string['dateto'] = 'To date';
$string['resetfilters'] = 'Reset';
$string['titlepreview'] = 'Title';
$string['postedby'] = 'Posted by';
$string['addannouncement'] = 'Add announcement';
$string['fieldsource:core'] = 'Standard user field';
$string['fieldsource:profile'] = 'Custom profile field';
$string['announcements'] = 'Announcements';
$string['manageannouncements'] = 'Manage announcements';
$string['createannouncement'] = 'Create announcement';
$string['editannouncement'] = 'Edit announcement';
$string['viewall'] = 'View all';
$string['noannouncements'] = 'No announcements are currently available.';
$string['audiencesummary'] = 'Target audience';
$string['targetedcount'] = 'Targeted';
$string['notifiedcount'] = 'Notified';
$string['actions'] = 'Actions';
$string['edit'] = 'Edit';
$string['archive'] = 'Archive';
$string['archivedsuccess'] = 'Announcement archived.';
$string['savesuccess'] = 'Announcement saved.';
$string['invalidtargetconfig'] = 'Target audience configuration is invalid.';
$string['invaliddatewindow'] = 'End date must be after the start date.';
$string['invalidfieldmatchvalue'] = 'This operator requires a filter value.';
$string['missingfieldselection'] = 'Select a user field.';
$string['notificationfullmessage'] = '{$a->message}';
$string['notificationfullmessagehtml'] = '{$a->message}';
$string['notificationsubject'] = '{$a->title}';
$string['eventviewed'] = 'Announcement viewed';
$string['privacy:metadata'] = 'The Dashboard announcements block stores announcement content and delivery logs.';
$string['privacy:metadata:block_dashboardannouncements'] = 'Information about announcements created in the dashboard announcements block.';
$string['privacy:metadata:block_dashboardannouncements:title'] = 'The announcement title.';
$string['privacy:metadata:block_dashboardannouncements:message'] = 'The announcement body text.';
$string['privacy:metadata:block_dashboardannouncements:status'] = 'The publication state of the announcement.';
$string['privacy:metadata:block_dashboardannouncements:timestart'] = 'The time when the announcement becomes visible.';
$string['privacy:metadata:block_dashboardannouncements:timeend'] = 'The time when the announcement stops being visible.';
$string['privacy:metadata:block_dashboardannouncements:targettype'] = 'The type of target audience for the announcement.';
$string['privacy:metadata:block_dashboardannouncements:targetconfigjson'] = 'The stored target audience configuration.';
$string['privacy:metadata:block_dashboardannouncements:sendmode'] = 'The selected delivery mode for the announcement.';
$string['privacy:metadata:block_dashboardannouncements:createdby'] = 'The user who created the announcement.';
$string['privacy:metadata:block_dashboardannouncements:modifiedby'] = 'The user who last modified the announcement.';
$string['privacy:metadata:filearea'] = 'Files attached to announcements.';
$string['privacy:metadata:block_dashboardannouncements_delqueue'] = 'Queued delivery jobs for dashboard announcements.';
$string['privacy:metadata:block_dashboardannouncements_delqueue:announcementid'] = 'The announcement associated with the queued job.';
$string['privacy:metadata:block_dashboardannouncements_delqueue:channel'] = 'The delivery channel being processed.';
$string['privacy:metadata:block_dashboardannouncements_delqueue:status'] = 'The status of the queued delivery job.';
$string['privacy:metadata:block_dashboardannouncements_delqueue:recipientsnapshotcount'] = 'The number of targeted users captured when the first delivery was queued.';
$string['privacy:metadata:block_dashboardannouncements_dellog'] = 'Per-user delivery records for dashboard announcements.';
$string['privacy:metadata:block_dashboardannouncements_dellog:announcementid'] = 'The announcement that was delivered.';
$string['privacy:metadata:block_dashboardannouncements_dellog:userid'] = 'The recipient user id.';
$string['privacy:metadata:block_dashboardannouncements_dellog:channel'] = 'The delivery channel used.';
$string['privacy:metadata:block_dashboardannouncements_dellog:status'] = 'The result status for the delivery attempt.';
$string['privacy:metadata:block_dashboardannouncements_dellog:errorinfo'] = 'Any error information captured during delivery.';
$string['taskprocessdelivery'] = 'Process dashboard announcement delivery queue';
$string['targetedblank'] = '-';
