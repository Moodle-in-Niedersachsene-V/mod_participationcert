<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace mod_participationcert;

use mod_participationcert\task\send_certificates;

/**
 * Queues the email delivery when a user's progress may have fulfilled the access restrictions.
 *
 * The check itself runs later in an ad hoc task, because the restrictions are evaluated more
 * reliably after the event has been processed completely and the user should not wait for the mail.
 *
 * @package    mod_participationcert
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * Queues a delivery for every certificate of the course that is sent by email and not yet sent
     * to the user.
     *
     * @param \core\event\base $event Completion or grade event with a related user.
     * @return void
     */
    public static function progress_changed(\core\event\base $event): void {
        global $DB;

        $userid = (int) $event->relateduserid;
        $courseid = (int) $event->courseid;
        if (!$userid || !$courseid || $courseid == SITEID) {
            return;
        }
        $sql = 'SELECT p.id
                  FROM {participationcert} p
             LEFT JOIN {participationcert_issues} i ON i.participationcertid = p.id AND i.userid = :userid
                 WHERE p.course = :courseid AND p.emailcertificate = 1
                   AND (i.id IS NULL OR i.timeemailed = 0)';
        $instanceids = $DB->get_fieldset_sql($sql, ['userid' => $userid, 'courseid' => $courseid]);
        if (!$instanceids) {
            return;
        }

        $modinfo = get_fast_modinfo($courseid);
        foreach ($modinfo->get_instances_of('participationcert') as $cm) {
            if (in_array((int) $cm->instance, array_map('intval', $instanceids), true)) {
                send_certificates::queue((int) $cm->id, $userid, false);
            }
        }
    }
}
