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

namespace mod_participationcert\local;

use cm_info;
use context_module;
use mod_participationcert\event\certificate_issued;
use mod_participationcert\event\issue_deleted;
use stdClass;

/**
 * Decides who may receive a certificate and keeps the issued certificates.
 *
 * A certificate is available to an active participant with the receive capability for whom the
 * activity is visible, which includes Moodle's access restrictions. The first download or email
 * issues it: the date of issue and a random code are stored and stay the same afterwards.
 *
 * @package    mod_participationcert
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class issue_manager {
    /** @var int Length of the certificate code. */
    public const CODE_LENGTH = 10;

    /** @var string Characters of the code, without 0, O, 1 and I which are easily confused. */
    private const CODE_CHARS = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * Tells whether the user may receive the certificate of this activity now.
     *
     * @param cm_info $cm Course module.
     * @param int $userid User id.
     * @return bool True when the user is an active participant and the restrictions are met.
     */
    public static function can_receive(cm_info $cm, int $userid): bool {
        $context = context_module::instance($cm->id);
        if (!is_enrolled($context, $userid, 'mod/participationcert:receive', true)) {
            return false;
        }
        return \core_availability\info_module::is_user_visible($cm, $userid, false);
    }

    /**
     * Returns the issued certificate of a user, or null if none was issued.
     *
     * @param int $instanceid Instance id.
     * @param int $userid User id.
     * @return stdClass|null Issue record.
     */
    public static function get(int $instanceid, int $userid): ?stdClass {
        global $DB;

        $issue = $DB->get_record('participationcert_issues', ['participationcertid' => $instanceid, 'userid' => $userid]);
        return $issue ?: null;
    }

    /**
     * Returns the issued certificate of a user and issues it first if needed.
     *
     * The caller checks with can_receive() that the user is entitled to the certificate.
     *
     * @param stdClass $instance Instance record.
     * @param context_module $context Module context.
     * @param int $userid User id.
     * @return stdClass Issue record.
     */
    public static function issue(stdClass $instance, context_module $context, int $userid): stdClass {
        global $DB;

        $existing = self::get((int) $instance->id, $userid);
        if ($existing) {
            return $existing;
        }
        $issue = (object) [
            'participationcertid' => (int) $instance->id,
            'userid' => $userid,
            'code' => self::new_code(),
            'timecreated' => time(),
            'timeemailed' => 0,
        ];
        $issue->id = $DB->insert_record('participationcert_issues', $issue);

        certificate_issued::create([
            'objectid' => $issue->id,
            'context' => $context,
            'relateduserid' => $userid,
        ])->trigger();
        return $issue;
    }

    /**
     * Records that the certificate was sent by email.
     *
     * @param stdClass $issue Issue record, updated in place.
     * @return void
     */
    public static function mark_emailed(stdClass $issue): void {
        global $DB;

        $issue->timeemailed = time();
        $DB->set_field('participationcert_issues', 'timeemailed', $issue->timeemailed, ['id' => $issue->id]);
    }

    /**
     * Returns all issued certificates of an activity with the names of their recipients.
     *
     * @param int $instanceid Instance id.
     * @return stdClass[] Issue records with user name fields, ordered by last name.
     */
    public static function get_all(int $instanceid): array {
        global $DB;

        // The name fields come from the core user fields API, not from input.
        $usersql = \core_user\fields::for_name()->get_sql('u', false, '', '', false)->selects;
        $sql = "SELECT i.id, i.userid, i.code, i.timecreated, i.timeemailed, $usersql
                  FROM {participationcert_issues} i
                  JOIN {user} u ON u.id = i.userid
                 WHERE i.participationcertid = :instanceid AND u.deleted = 0
              ORDER BY u.lastname ASC, u.firstname ASC, i.id ASC";
        return $DB->get_records_sql($sql, ['instanceid' => $instanceid]);
    }

    /**
     * Deletes one issued certificate. The user receives a new date and code on the next download.
     *
     * @param stdClass $issue Issue record.
     * @param context_module $context Module context.
     * @return void
     */
    public static function delete(stdClass $issue, context_module $context): void {
        global $DB;

        $DB->delete_records('participationcert_issues', ['id' => $issue->id]);
        issue_deleted::create([
            'objectid' => $issue->id,
            'context' => $context,
            'relateduserid' => $issue->userid,
        ])->trigger();
    }

    /**
     * Deletes all issued certificates of an activity, for instance deletion and course reset.
     *
     * @param int $instanceid Instance id.
     * @return void
     */
    public static function delete_all(int $instanceid): void {
        global $DB;

        $DB->delete_records('participationcert_issues', ['participationcertid' => $instanceid]);
    }

    /**
     * Returns a random code that is not used by any certificate yet.
     *
     * @return string Code.
     */
    private static function new_code(): string {
        global $DB;

        do {
            $code = '';
            for ($i = 0; $i < self::CODE_LENGTH; $i++) {
                $code .= self::CODE_CHARS[random_int(0, strlen(self::CODE_CHARS) - 1)];
            }
        } while ($DB->record_exists('participationcert_issues', ['code' => $code]));
        return $code;
    }
}
