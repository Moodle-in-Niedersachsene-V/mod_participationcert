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
 * Overview of the issued certificates with download, sending by email and deletion.
 *
 * @package    mod_participationcert
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\output\notification;
use mod_participationcert\local\delivery;
use mod_participationcert\local\issue_manager;
use mod_participationcert\task\send_certificates;

require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$userid = optional_param('userid', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

[$course, $cm] = get_course_and_cm_from_cmid($id, 'participationcert');
$instance = $DB->get_record('participationcert', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/participationcert:viewreport', $context);

$url = new moodle_url('/mod/participationcert/report.php', ['id' => $cm->id]);
$PAGE->set_url($url);
$PAGE->set_title(format_string($instance->name) . ': ' . get_string('issuedcertificates', 'participationcert'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_activity_record($instance);

// Actions change data or send mail and are accepted only by POST with a valid session key.
if ($action !== '' && data_submitted()) {
    require_sesskey();
    require_capability('mod/participationcert:manage', $context);
    if ($action === 'sendall') {
        send_certificates::queue((int) $cm->id, 0, false);
        redirect($url, get_string('sendallqueued', 'participationcert'), null, notification::NOTIFY_SUCCESS);
    }
    $issue = issue_manager::get((int) $instance->id, $userid);
    if (!$issue) {
        throw new moodle_exception('error:noissue', 'participationcert');
    }
    $user = core_user::get_user($userid, '*', MUST_EXIST);
    if ($action === 'resend') {
        if (!delivery::run($cm, $instance, $userid, true)) {
            $message = get_string('notavailableforuser', 'participationcert', fullname($user));
            redirect($url, $message, null, notification::NOTIFY_WARNING);
        }
        redirect($url, get_string('certificatesent', 'participationcert', fullname($user)), null, notification::NOTIFY_SUCCESS);
    }
    if ($action === 'delete' && $confirm) {
        issue_manager::delete($issue, $context);
        redirect($url, get_string('issuedeleted', 'participationcert', fullname($user)), null, notification::NOTIFY_SUCCESS);
    }
    if ($action === 'delete') {
        echo $OUTPUT->header();
        echo $OUTPUT->confirm(
            get_string('deleteissueconfirm', 'participationcert', fullname($user)),
            new moodle_url($url, ['action' => 'delete', 'userid' => $userid, 'confirm' => 1]),
            $url
        );
        echo $OUTPUT->footer();
        exit;
    }
    throw new moodle_exception('error:unknownaction', 'participationcert');
}

$canmanage = has_capability('mod/participationcert:manage', $context);
$issues = issue_manager::get_all((int) $instance->id);
$dateformat = get_string('strftimedatetimeshort', 'langconfig');

echo $OUTPUT->header();
echo $OUTPUT->heading(
    get_string('issuedcertificates', 'participationcert') . $OUTPUT->help_icon('issuedcertificates', 'participationcert')
);

if ($canmanage) {
    echo $OUTPUT->single_button(
        new moodle_url($url, ['action' => 'sendall']),
        get_string('sendall', 'participationcert'),
        'post'
    );
    echo $OUTPUT->help_icon('sendall', 'participationcert');
}

if (!$issues) {
    echo $OUTPUT->notification(get_string('noissues', 'participationcert'), notification::NOTIFY_INFO);
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [
    get_string('fullname'),
    get_string('dateissued', 'participationcert'),
    get_string('code', 'participationcert'),
    get_string('dateemailed', 'participationcert'),
    get_string('actions'),
];
$table->data = [];
foreach ($issues as $issue) {
    $buttons = [
        $OUTPUT->single_button(
            new moodle_url('/mod/participationcert/download.php', ['id' => $cm->id, 'userid' => $issue->userid]),
            get_string('download'),
            'post',
            ['type' => single_button::BUTTON_SECONDARY]
        ),
    ];
    if ($canmanage) {
        $buttons[] = $OUTPUT->single_button(
            new moodle_url($url, ['action' => 'resend', 'userid' => $issue->userid]),
            get_string('sendbyemail', 'participationcert'),
            'post',
            ['type' => single_button::BUTTON_SECONDARY]
        );
        $buttons[] = $OUTPUT->single_button(
            new moodle_url($url, ['action' => 'delete', 'userid' => $issue->userid]),
            get_string('delete'),
            'post',
            ['type' => single_button::BUTTON_DANGER]
        );
    }
    $table->data[] = [
        html_writer::link(
            new moodle_url('/user/view.php', ['id' => $issue->userid, 'course' => $course->id]),
            s(fullname($issue))
        ),
        userdate($issue->timecreated, $dateformat),
        s($issue->code),
        $issue->timeemailed ? userdate($issue->timeemailed, $dateformat) : '-',
        // The buttons are HTML rendered by the core output renderer, not user input.
        html_writer::div(implode('', $buttons), 'd-flex flex-wrap gap-2'),
    ];
}
echo html_writer::table($table);

echo $OUTPUT->footer();
