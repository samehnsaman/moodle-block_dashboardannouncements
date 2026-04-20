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

namespace block_dashboardannouncements\privacy;

defined('MOODLE_INTERNAL') || die();

use block_dashboardannouncements\local\announcement_manager;
use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\provider as metadata_provider;

/**
 * Privacy provider for dashboard announcements.
 *
 * @package   block_dashboardannouncements
 * @copyright 2026 OpenAI
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements metadata_provider {
    /**
     * Explain stored data.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('block_dashboardannouncements', [
            'title' => 'privacy:metadata:block_dashboardannouncements:title',
            'message' => 'privacy:metadata:block_dashboardannouncements:message',
            'status' => 'privacy:metadata:block_dashboardannouncements:status',
            'timestart' => 'privacy:metadata:block_dashboardannouncements:timestart',
            'timeend' => 'privacy:metadata:block_dashboardannouncements:timeend',
            'targettype' => 'privacy:metadata:block_dashboardannouncements:targettype',
            'targetconfigjson' => 'privacy:metadata:block_dashboardannouncements:targetconfigjson',
            'sendmode' => 'privacy:metadata:block_dashboardannouncements:sendmode',
            'showaspopup' => 'privacy:metadata:block_dashboardannouncements:showaspopup',
            'createdby' => 'privacy:metadata:block_dashboardannouncements:createdby',
            'modifiedby' => 'privacy:metadata:block_dashboardannouncements:modifiedby',
        ], 'privacy:metadata:block_dashboardannouncements');

        $collection->add_database_table('block_dashann_delqueue', [
            'announcementid' => 'privacy:metadata:block_dashann_delqueue:announcementid',
            'channel' => 'privacy:metadata:block_dashann_delqueue:channel',
            'status' => 'privacy:metadata:block_dashann_delqueue:status',
            'recipientsnapshotcount' => 'privacy:metadata:block_dashann_delqueue:recipientsnapshotcount',
        ], 'privacy:metadata:block_dashann_delqueue');

        $collection->add_database_table('block_dashann_dellog', [
            'announcementid' => 'privacy:metadata:block_dashann_dellog:announcementid',
            'userid' => 'privacy:metadata:block_dashann_dellog:userid',
            'channel' => 'privacy:metadata:block_dashann_dellog:channel',
            'status' => 'privacy:metadata:block_dashann_dellog:status',
            'errorinfo' => 'privacy:metadata:block_dashann_dellog:errorinfo',
        ], 'privacy:metadata:block_dashann_dellog');

        $collection->add_user_preference(
            announcement_manager::POPUP_SEEN_PREFERENCE_PREFIX . '{announcementid}',
            'privacy:metadata:preference:popupseen'
        );

        return $collection;
    }
}
