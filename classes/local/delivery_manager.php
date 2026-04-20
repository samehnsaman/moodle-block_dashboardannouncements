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

/**
 * Handles queue creation, delivery logging and admin statistics.
 *
 * @package   block_dashboardannouncements
 * @copyright 2026 OpenAI
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delivery_manager {
    /** @var audience_resolver */
    protected $resolver;

    public function __construct(?audience_resolver $resolver = null) {
        $this->resolver = $resolver ?? new audience_resolver();
    }

    /**
     * Queue initial delivery for an announcement.
     *
     * @param \stdClass $announcement
     * @return int|null
     */
    public function queue_announcement_delivery(\stdClass $announcement): ?int {
        global $DB;

        if ($announcement->sendmode === 'none' || !empty($announcement->firstdeliveryqueued)) {
            return null;
        }

        $recipients = $this->resolver->get_target_users($announcement);
        $queue = (object)[
            'announcementid' => $announcement->id,
            'channel' => 'message',
            'status' => 'pending',
            'recipientsnapshotcount' => count($recipients),
            'attempts' => 0,
            'lasterror' => null,
            'timecreated' => time(),
            'timestarted' => null,
            'timefinished' => null,
        ];

        $queueid = $DB->insert_record('block_dashann_delqueue', $queue);
        $DB->set_field('block_dashboardannouncements', 'firstdeliveryqueued', 1, ['id' => $announcement->id]);

        return $queueid;
    }

    /**
     * Fetch pending queue items.
     *
     * @param int $limit
     * @return array
     */
    public function get_pending_queue_items(int $limit = 20): array {
        global $DB;
        return $DB->get_records('block_dashann_delqueue', ['status' => 'pending'], 'timecreated ASC', '*', 0, $limit);
    }

    /**
     * Mark queue processing start.
     *
     * @param \stdClass $queue
     * @return void
     */
    public function mark_queue_processing(\stdClass $queue): void {
        global $DB;

        $queue->status = 'processing';
        $queue->timestarted = time();
        $queue->attempts = ((int)$queue->attempts) + 1;
        $DB->update_record('block_dashann_delqueue', $queue);
    }

    /**
     * Finalise queue row.
     *
     * @param \stdClass $queue
     * @param string $status
     * @param string|null $error
     * @return void
     */
    public function finish_queue(\stdClass $queue, string $status, ?string $error = null): void {
        global $DB;

        $queue->status = $status;
        $queue->lasterror = $error;
        $queue->timefinished = time();
        $DB->update_record('block_dashann_delqueue', $queue);
    }

    /**
     * Check if a user already received a successful send on a channel.
     *
     * @param int $announcementid
     * @param int $userid
     * @param string $channel
     * @return bool
     */
    public function was_successfully_sent(int $announcementid, int $userid, string $channel = 'message'): bool {
        global $DB;

        return $DB->record_exists('block_dashann_dellog', [
            'announcementid' => $announcementid,
            'userid' => $userid,
            'channel' => $channel,
            'status' => 'sent',
        ]);
    }

    /**
     * Write or update a delivery log row.
     *
     * @param int $queueid
     * @param int $announcementid
     * @param int $userid
     * @param string $channel
     * @param string $status
     * @param string|null $messageid
     * @param string|null $errorinfo
     * @return void
     */
    public function log_delivery_result(
        int $queueid,
        int $announcementid,
        int $userid,
        string $channel,
        string $status,
        ?string $messageid = null,
        ?string $errorinfo = null
    ): void {
        global $DB;

        $existing = $DB->get_record('block_dashann_dellog', [
            'announcementid' => $announcementid,
            'userid' => $userid,
            'channel' => $channel,
        ]);

        $record = (object)[
            'queueid' => $queueid,
            'announcementid' => $announcementid,
            'userid' => $userid,
            'channel' => $channel,
            'status' => $status,
            'messageid' => $messageid,
            'errorinfo' => $errorinfo,
            'timeprocessed' => time(),
        ];

        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('block_dashann_dellog', $record);
            return;
        }

        $DB->insert_record('block_dashann_dellog', $record);
    }

    /**
     * Return admin counts by announcement.
     *
     * @param array $announcements
     * @return array<int, array{targeted:?int, notified:int}>
     */
    public function get_admin_stats_for_announcements(array $announcements): array {
        global $DB;

        if (!$announcements) {
            return [];
        }

        $announcementids = array_map(static function($announcement) {
            return (int)$announcement->id;
        }, $announcements);

        [$insql, $params] = $DB->get_in_or_equal($announcementids, SQL_PARAMS_NAMED, 'ann');

        $stats = [];
        foreach ($announcementids as $id) {
            $stats[$id] = ['targeted' => null, 'notified' => 0];
        }

        $snapshotsql = "SELECT announcementid, MIN(id) AS firstqueueid
                          FROM {block_dashann_delqueue}
                         WHERE announcementid {$insql}
                      GROUP BY announcementid";
        $snapshots = $DB->get_records_sql($snapshotsql, $params);
        foreach ($snapshots as $snapshot) {
            $queue = $DB->get_record('block_dashann_delqueue', ['id' => $snapshot->firstqueueid], 'announcementid, recipientsnapshotcount');
            if ($queue) {
                $stats[(int)$queue->announcementid]['targeted'] = $queue->recipientsnapshotcount === null
                    ? null
                    : (int)$queue->recipientsnapshotcount;
            }
        }

        $notifiedsql = "SELECT announcementid, COUNT(DISTINCT userid) AS notifiedcount
                          FROM {block_dashann_dellog}
                         WHERE announcementid {$insql}
                           AND status = :status
                      GROUP BY announcementid";
        $notifiedparams = $params + ['status' => 'sent'];
        $notified = $DB->get_records_sql($notifiedsql, $notifiedparams);
        foreach ($notified as $row) {
            $stats[(int)$row->announcementid]['notified'] = (int)$row->notifiedcount;
        }

        return $stats;
    }
}
