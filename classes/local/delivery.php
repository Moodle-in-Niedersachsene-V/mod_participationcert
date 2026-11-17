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
use mod_participationcert\event\certificate_sent;
use moodle_url;
use stdClass;

/**
 * Sends certificates by email as PDF attachment.
 *
 * A certificate is sent only to users who may receive it. Without force, a certificate that was
 * already sent is not sent again, so repeated runs for the same users are harmless.
 *
 * @package    mod_participationcert
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delivery {
    /**
     * Issues and sends the certificate to one user or to all participants who may receive it.
     *
     * @param cm_info $cm Course module.
     * @param stdClass $instance Instance record.
     * @param int $userid User id, or 0 for all participants with the receive capability.
     * @param bool $force Send again even if the certificate was already sent.
     * @return int Number of emails sent.
     */
    public static function run(cm_info $cm, stdClass $instance, int $userid, bool $force): int {
        $context = context_module::instance($cm->id);
        if ($userid) {
            $userids = [$userid];
        } else {
            $users = get_enrolled_users($context, 'mod/participationcert:receive', 0, 'u.id', 'u.id ASC', 0, 0, true);
            $userids = array_map('intval', array_keys($users));
        }

        $sent = 0;
        foreach ($userids as $id) {
            if (!issue_manager::can_receive($cm, $id)) {
                continue;
            }
            $existing = issue_manager::get((int) $instance->id, $id);
            if (!$force && $existing && $existing->timeemailed > 0) {
                continue;
            }
            $issue = $existing ?? issue_manager::issue($instance, $context, $id);
            self::send($instance, $cm, \core_user::get_user($id, '*', MUST_EXIST), $issue);
            $sent++;
        }
        return $sent;
    }

    /**
     * Sends an issued certificate to its recipient, in the recipient's language.
     *
     * @param stdClass $instance Instance record.
     * @param cm_info $cm Course module.
     * @param stdClass $user Recipient.
     * @param stdClass $issue Issue record of the recipient, updated in place.
     * @return void
     * @throws \moodle_exception When the mail could not be handed over.
     */
    public static function send(stdClass $instance, cm_info $cm, stdClass $user, stdClass $issue): void {
        $context = context_module::instance($cm->id);
        $course = get_course($cm->course);

        $previouslanguage = force_current_language($user->lang ?? '');
        try {
            $pdf = certificate_pdf::build($instance, $course, $user, $issue, $context);
            $filename = certificate_pdf::filename($instance, $user);
            $a = (object) [
                'firstname' => $user->firstname,
                'course' => format_string($course->fullname, true, ['context' => $context, 'escape' => false]),
                'url' => (new moodle_url('/mod/participationcert/view.php', ['id' => $cm->id]))->out(false),
            ];
            $subject = get_string('emailsubject', 'participationcert', $a);
            $text = get_string('emailbody', 'participationcert', $a);
        } finally {
            force_current_language($previouslanguage);
        }

        $path = make_request_directory() . '/' . $filename;
        file_put_contents($path, $pdf);
        $ok = email_to_user(
            $user,
            \core_user::get_noreply_user(),
            $subject,
            $text,
            text_to_html($text, false, false, true),
            $path,
            $filename
        );
        if (!$ok) {
            throw new \moodle_exception('error:emailfailed', 'participationcert', '', fullname($user));
        }

        issue_manager::mark_emailed($issue);
        certificate_sent::create([
            'objectid' => $issue->id,
            'context' => $context,
            'relateduserid' => $user->id,
        ])->trigger();
    }
}
