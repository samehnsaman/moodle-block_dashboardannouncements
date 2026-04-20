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

namespace block_dashboardannouncements\local;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');

/**
 * Coordinates CRUD and visibility retrieval for announcements.
 *
 * @package   block_dashboardannouncements
 * @copyright 2026 OpenAI
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class announcement_manager {
    public const ATTACHMENT_FILEAREA = 'attachment';
    public const POPUP_SEEN_PREFERENCE_PREFIX = 'block_dashboardannouncements_popup_seen_';

    /** @var audience_resolver */
    protected $resolver;

    /** @var delivery_manager */
    protected $deliverymanager;

    public function __construct(
        ?audience_resolver $resolver = null,
        ?delivery_manager $deliverymanager = null
    ) {
        $this->resolver = $resolver ?? new audience_resolver();
        $this->deliverymanager = $deliverymanager ?? new delivery_manager($this->resolver);
    }

    /**
     * Save or update announcement from form-style data.
     *
     * @param \stdClass $data
     * @param int $userid
     * @return int
     */
    public function save_announcement(\stdClass $data, int $userid): int {
        global $DB;

        $targetconfig = $this->normalise_target_config($data);
        if (!$this->resolver->is_valid_target_config($data->targettype, $targetconfig)) {
            throw new \moodle_exception('invalidtargetconfig', 'block_dashboardannouncements');
        }

        $timestart = !empty($data->timestart) ? (int)$data->timestart : 0;
        $timeend = !empty($data->timeend) ? (int)$data->timeend : null;
        if ($timeend !== null && $timestart > 0 && $timeend < $timestart) {
            throw new \moodle_exception('invaliddatewindow', 'block_dashboardannouncements');
        }

        $record = (object)[
            'title' => trim($data->title),
            'message' => $data->message['text'] ?? $data->message,
            'status' => $data->status,
            'timestart' => $timestart,
            'timeend' => $timeend,
            'targettype' => $data->targettype,
            'targetconfigjson' => $this->resolver->encode_target_config($targetconfig),
            'sendmode' => $data->sendmode,
            'showaspopup' => !empty($data->showaspopup) ? 1 : 0,
            'archived' => !empty($data->archived) ? 1 : 0,
            'timemodified' => time(),
            'modifiedby' => $userid,
        ];

        if (!empty($data->id)) {
            $record->id = (int)$data->id;
            $existing = $this->get_announcement($record->id, MUST_EXIST);
            $record->timecreated = $existing->timecreated;
            $record->createdby = $existing->createdby;
            $record->firstdeliveryqueued = $existing->firstdeliveryqueued;
            $DB->update_record('block_dashboardannouncements', $record);
            $this->save_attachment_draft((int)$record->id, (int)($data->attachment_filemanager ?? 0));
            return $record->id;
        }

        $record->timecreated = time();
        $record->createdby = $userid;
        $record->firstdeliveryqueued = 0;
        $id = $DB->insert_record('block_dashboardannouncements', $record);
        $this->save_attachment_draft((int)$id, (int)($data->attachment_filemanager ?? 0));
        return $id;
    }

    /**
     * Queue first delivery when applicable after save.
     *
     * @param int $announcementid
     * @return void
     */
    public function queue_first_delivery_if_required(int $announcementid): void {
        $announcement = $this->get_announcement($announcementid, MUST_EXIST);
        if ($announcement->status === 'published') {
            $this->deliverymanager->queue_announcement_delivery($announcement);
        }
    }

    /**
     * Archive announcement.
     *
     * @param int $id
     * @param int $userid
     * @return void
     */
    public function archive_announcement(int $id, int $userid): void {
        global $DB;
        $announcement = $this->get_announcement($id, MUST_EXIST);
        $announcement->archived = 1;
        $announcement->status = 'archived';
        $announcement->timemodified = time();
        $announcement->modifiedby = $userid;
        $DB->update_record('block_dashboardannouncements', $announcement);
    }

    /**
     * Get one announcement.
     *
     * @param int $id
     * @param int $strictness
     * @return \stdClass|false
     */
    public function get_announcement(int $id, int $strictness = IGNORE_MISSING) {
        global $DB;
        return $DB->get_record('block_dashboardannouncements', ['id' => $id], '*', $strictness);
    }

    /**
     * Get announcements visible to a user.
     *
     * @param int $userid
     * @param int|null $limit
     * @return array
     */
    public function get_announcements_for_user(int $userid, ?int $limit = null): array {
        global $DB;

        $now = time();
        $records = $DB->get_records_select(
            'block_dashboardannouncements',
            'archived = 0 AND status = :status AND timestart <= :now AND (timeend IS NULL OR timeend = 0 OR timeend >= :now2)',
            ['status' => 'published', 'now' => $now, 'now2' => $now],
            'timecreated DESC'
        );

        $matched = [];
        foreach ($records as $record) {
            if ($this->resolver->user_matches($record, $userid)) {
                $matched[] = $record;
            }
        }

        if ($limit !== null) {
            return array_slice($matched, 0, $limit);
        }

        return $matched;
    }

    /**
     * Get the latest popup announcement a user should see.
     *
     * Only the latest eligible popup is considered. If the latest popup has already
     * been shown to the user, no older popup will be returned.
     *
     * @param int $userid
     * @return \stdClass|null
     */
    public function get_latest_popup_announcement_for_user(int $userid): ?\stdClass {
        global $DB;

        $now = time();
        $records = $DB->get_records_select(
            'block_dashboardannouncements',
            'archived = 0 AND status = :status AND showaspopup = :showaspopup AND timestart <= :now AND (timeend IS NULL OR timeend = 0 OR timeend >= :now2)',
            [
                'status' => 'published',
                'showaspopup' => 1,
                'now' => $now,
                'now2' => $now,
            ],
            'timecreated DESC, id DESC'
        );

        if (!$records) {
            return null;
        }

        $announcement = null;
        foreach ($records as $record) {
            if ($this->resolver->user_matches($record, $userid)) {
                $announcement = $record;
                break;
            }
        }

        if ($announcement === null) {
            return null;
        }

        if ($this->has_user_seen_popup_announcement($userid, (int)$announcement->id)) {
            return null;
        }

        return $announcement;
    }

    /**
     * Mark a popup announcement as seen for a user.
     *
     * @param int $userid
     * @param int $announcementid
     * @return void
     */
    public function mark_user_seen_popup_announcement(int $userid, int $announcementid): void {
        if ($userid <= 0 || $announcementid <= 0) {
            return;
        }

        set_user_preference($this->get_popup_seen_preference_key($announcementid), '1', $userid);
    }

    /**
     * Determine whether a user has already seen a popup announcement.
     *
     * @param int $userid
     * @param int $announcementid
     * @return bool
     */
    public function has_user_seen_popup_announcement(int $userid, int $announcementid): bool {
        if ($userid <= 0 || $announcementid <= 0) {
            return false;
        }

        return get_user_preferences($this->get_popup_seen_preference_key($announcementid), '', $userid) === '1';
    }

    /**
     * Build preference key for popup seen-state storage.
     *
     * @param int $announcementid
     * @return string
     */
    protected function get_popup_seen_preference_key(int $announcementid): string {
        return self::POPUP_SEEN_PREFERENCE_PREFIX . $announcementid;
    }

    /**
     * Get announcements for admin management.
     *
     * @return array
     */
    public function get_all_announcements_for_management(): array {
        global $DB;

        $announcements = $DB->get_records('block_dashboardannouncements', null, 'timecreated DESC');
        $stats = $this->deliverymanager->get_admin_stats_for_announcements($announcements);
        foreach ($announcements as $announcement) {
            $announcement->audiencesummary = $this->resolver->get_audience_summary($announcement);
            $announcement->targetedcountsnapshot = $stats[$announcement->id]['targeted'] ?? null;
            $announcement->notifieduniquecount = $stats[$announcement->id]['notified'] ?? 0;
        }

        return $announcements;
    }

    /**
     * Determine whether a user may view a specific announcement.
     *
     * @param \stdClass $announcement
     * @param int $userid
     * @return bool
     */
    public function user_can_view_announcement(\stdClass $announcement, int $userid): bool {
        $now = time();
        if ((int)$announcement->archived === 1 || $announcement->status !== 'published') {
            return false;
        }

        if (!empty($announcement->timestart) && (int)$announcement->timestart > $now) {
            return false;
        }

        if (!empty($announcement->timeend) && (int)$announcement->timeend < $now) {
            return false;
        }

        return $this->resolver->user_matches($announcement, $userid);
    }

    /**
     * Normalise target config from submitted data.
     *
     * @param \stdClass $data
     * @return array
     */
    protected function normalise_target_config(\stdClass $data): array {
        switch ($data->targettype) {
            case audience_resolver::TARGET_CATEGORY:
                return ['categoryids' => array_map('intval', $data->categoryids ?? [])];

            case audience_resolver::TARGET_COHORT:
                return ['cohortids' => array_map('intval', $data->cohortids ?? [])];

            case audience_resolver::TARGET_FIELD:
                [$fieldsource, $fieldkey] = $this->resolver->parse_field_lookup((string)($data->fieldlookup ?? ''));
                $fieldlabel = $this->resolver->get_field_label($fieldsource, $fieldkey);
                return [
                    'fieldsource' => $fieldsource,
                    'fieldkey' => $fieldkey,
                    'fieldlabel' => $fieldlabel,
                    'operator' => (string)($data->fieldoperator ?? audience_resolver::OP_EQUAL),
                    'matchvalue' => (string)($data->fieldmatchvalue ?? ''),
                ];

            case audience_resolver::TARGET_ALL:
            default:
                return [];
        }
    }

    /**
     * Prepare filemanager draft item id for the attachment field.
     *
     * @param int $announcementid
     * @return int
     */
    public function prepare_attachment_draft_itemid(int $announcementid = 0): int {
        $draftitemid = file_get_submitted_draft_itemid('attachment_filemanager');
        file_prepare_draft_area(
            $draftitemid,
            \context_system::instance()->id,
            'block_dashboardannouncements',
            self::ATTACHMENT_FILEAREA,
            $announcementid,
            ['subdirs' => 0, 'maxfiles' => 1]
        );

        return $draftitemid;
    }

    /**
     * Save attachment from filemanager draft area.
     *
     * @param int $announcementid
     * @param int $draftitemid
     * @return void
     */
    public function save_attachment_draft(int $announcementid, int $draftitemid): void {
        if ($announcementid <= 0) {
            return;
        }

        file_save_draft_area_files(
            $draftitemid,
            \context_system::instance()->id,
            'block_dashboardannouncements',
            self::ATTACHMENT_FILEAREA,
            $announcementid,
            ['subdirs' => 0, 'maxfiles' => 1]
        );
    }

    /**
     * Return the first attachment file if it exists.
     *
     * @param int $announcementid
     * @return \stored_file|false
     */
    public function get_attachment_file(int $announcementid) {
        $fs = get_file_storage();
        $files = $fs->get_area_files(
            \context_system::instance()->id,
            'block_dashboardannouncements',
            self::ATTACHMENT_FILEAREA,
            $announcementid,
            'filename',
            false
        );

        if (!$files) {
            return false;
        }

        return reset($files);
    }
}
