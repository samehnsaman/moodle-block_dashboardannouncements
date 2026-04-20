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
$string['dashboardannouncements:view'] = 'View dashboard announcements';
$string['dashboardannouncements:viewall'] = 'View all dashboard announcements';

$string['title'] = 'Title';
$string['date'] = 'Date';
$string['message'] = 'Message';
$string['messagepreview'] = 'Message preview';
$string['status'] = 'Status';
$string['status:draft'] = 'Draft';
$string['status:published'] = 'Published';
$string['status:disabled'] = 'Disabled';
$string['status:archived'] = 'Archived';
$string['status:unknown'] = 'Unknown status';
$string['startdate'] = 'Start date';
$string['enddate'] = 'End date';
$string['showaspopup'] = 'Show as popup on login';
$string['popuptitle'] = 'Announcement popup';
$string['popupreadannouncement'] = 'Read announcement';
$string['popupclose'] = 'Close';
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
$string['formsection:content'] = 'Content';
$string['formsection:schedule'] = 'Schedule';
$string['formsection:audience'] = 'Audience';
$string['formsection:delivery'] = 'Delivery';
$string['formsection:attachment'] = 'Attachment';
$string['formsection:recordstate'] = 'Record state';
$string['attachment'] = 'Attachment';
$string['attachmentdownload'] = 'Download attachment';
$string['noattachment'] = 'No attachment';
$string['search'] = 'Search';
$string['filter'] = 'Filter';
$string['datefrom'] = 'From date';
$string['dateto'] = 'To date';
$string['resetfilters'] = 'Reset';
$string['titlepreview'] = 'Title';
$string['postedby'] = 'Posted by';
$string['submittedon'] = 'Submitted: {$a}';
$string['addannouncement'] = 'Add announcement';
$string['fieldsource:core'] = 'Standard user field';
$string['fieldsource:profile'] = 'Custom profile field';
$string['corefield:username'] = 'Username';
$string['corefield:firstname'] = 'First name';
$string['corefield:lastname'] = 'Surname';
$string['corefield:email'] = 'Email address';
$string['corefield:idnumber'] = 'ID number';
$string['corefield:institution'] = 'Institution';
$string['corefield:department'] = 'Department';
$string['corefield:address'] = 'Address';
$string['corefield:city'] = 'City/town';
$string['corefield:country'] = 'Country';
$string['corefield:phone1'] = 'Phone';
$string['corefield:phone2'] = 'Mobile phone';
$string['corefield:url'] = 'Web page';
$string['announcements'] = 'Announcements';
$string['manageannouncements'] = 'Manage announcements';
$string['createannouncement'] = 'Create announcement';
$string['editannouncement'] = 'Edit announcement';
$string['viewall'] = 'View all';
$string['noannouncements'] = 'No announcements are currently available.';
$string['emptystate:title'] = 'No announcements available';
$string['emptystate:description'] = 'Announcements will appear here when they are available to you.';
$string['emptystate:list:title'] = 'No announcements match your filters';
$string['emptystate:list:description'] = 'Adjust search or date filters to find announcements.';
$string['emptystate:manage:title'] = 'No announcements to manage';
$string['emptystate:manage:description'] = 'Create an announcement to start publishing updates.';
$string['metadatanotavailable'] = 'Not available';
$string['audiencesummary'] = 'Target audience';
$string['targetedcount'] = 'Recipients targeted';
$string['notifiedcount'] = 'Recipients notified';
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
$string['privacy:metadata:block_dashboardannouncements:showaspopup'] = 'Whether the announcement should be shown as a login popup.';
$string['privacy:metadata:block_dashboardannouncements:createdby'] = 'The user who created the announcement.';
$string['privacy:metadata:block_dashboardannouncements:modifiedby'] = 'The user who last modified the announcement.';
$string['privacy:metadata:filearea'] = 'Files attached to announcements.';
$string['privacy:metadata:block_dashann_delqueue'] = 'Queued delivery jobs for dashboard announcements.';
$string['privacy:metadata:block_dashann_delqueue:announcementid'] = 'The announcement associated with the queued job.';
$string['privacy:metadata:block_dashann_delqueue:channel'] = 'The delivery channel being processed.';
$string['privacy:metadata:block_dashann_delqueue:status'] = 'The status of the queued delivery job.';
$string['privacy:metadata:block_dashann_delqueue:recipientsnapshotcount'] = 'The number of targeted users captured when the first delivery was queued.';
$string['privacy:metadata:block_dashann_dellog'] = 'Per-user delivery records for dashboard announcements.';
$string['privacy:metadata:block_dashann_dellog:announcementid'] = 'The announcement that was delivered.';
$string['privacy:metadata:block_dashann_dellog:userid'] = 'The recipient user id.';
$string['privacy:metadata:block_dashann_dellog:channel'] = 'The delivery channel used.';
$string['privacy:metadata:block_dashann_dellog:status'] = 'The result status for the delivery attempt.';
$string['privacy:metadata:block_dashann_dellog:errorinfo'] = 'Any error information captured during delivery.';
$string['privacy:metadata:preference:popupseen'] = 'Tracks which popup announcement IDs a user has already seen.';
$string['taskprocessdelivery'] = 'Process dashboard announcement delivery queue';
$string['targetedblank'] = 'Not captured';
