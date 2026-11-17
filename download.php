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

/**
 * Sends a certificate as PDF.
 *
 * Without userid, participants download their own certificate, which is issued on the first
 * download. With userid, teachers download a certificate that was already issued.
 *
 * @package    mod_participationcert
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_participationcert\local\certificate_pdf;
use mod_participationcert\local\issue_manager;

require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);

[$course, $cm] = get_course_and_cm_from_cmid($id, 'participationcert');
$instance = $DB->get_record('participationcert', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
// The first download stores the issue, so the request must come from the button with a session key.
require_sesskey();

if (!$userid || $userid == $USER->id) {
    require_capability('mod/participationcert:receive', $context, null, false);
    if (!issue_manager::can_receive($cm, (int) $USER->id)) {
        throw new moodle_exception('notavailable', 'participationcert');
    }
    $user = $USER;
    $issue = issue_manager::issue($instance, $context, (int) $USER->id);
} else {
    require_capability('mod/participationcert:viewreport', $context);
    $issue = issue_manager::get((int) $instance->id, $userid);
    if (!$issue) {
        throw new moodle_exception('error:noissue', 'participationcert');
    }
    $user = core_user::get_user($userid, '*', MUST_EXIST);
}

$pdf = certificate_pdf::build($instance, $course, $user, $issue, $context);
send_file($pdf, certificate_pdf::filename($instance, $user), 0, 0, true, true, 'application/pdf');
