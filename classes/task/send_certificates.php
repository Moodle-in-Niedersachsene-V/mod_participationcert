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

namespace mod_participationcert\task;

use mod_participationcert\local\delivery;

/**
 * Ad hoc task that sends certificates by email.
 *
 * It runs once per request on the next cron run; there is no scheduled task in this plugin.
 * Custom data: cmid, userid (0 for all participants) and force.
 *
 * @package    mod_participationcert
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_certificates extends \core\task\adhoc_task {
    /**
     * Queues a delivery; an identical waiting task is not queued twice.
     *
     * @param int $cmid Course module id.
     * @param int $userid User id, or 0 for all participants.
     * @param bool $force Send again even if the certificate was already sent.
     * @return void
     */
    public static function queue(int $cmid, int $userid, bool $force): void {
        $task = new self();
        $task->set_custom_data(['cmid' => $cmid, 'userid' => $userid, 'force' => $force]);
        $task->set_component('mod_participationcert');
        \core\task\manager::queue_adhoc_task($task, true);
    }

    /**
     * Returns the task name for the task overview.
     *
     * @return string Task name.
     */
    public function get_name() {
        return get_string('tasksendcertificates', 'participationcert');
    }

    /**
     * Sends the certificates. A deleted activity ends the task without sending.
     *
     * @return void
     */
    public function execute() {
        global $DB;

        $data = $this->get_custom_data();
        $cmid = (int) $data->cmid;
        $cmrecord = get_coursemodule_from_id('participationcert', $cmid);
        if (!$cmrecord) {
            mtrace("Course module $cmid no longer exists, nothing to send.");
            return;
        }
        $cm = get_fast_modinfo($cmrecord->course)->get_cm($cmid);
        $instance = $DB->get_record('participationcert', ['id' => $cm->instance], '*', MUST_EXIST);
        $sent = delivery::run($cm, $instance, (int) $data->userid, !empty($data->force));
        mtrace("Sent $sent certificate(s) for course module $cmid.");
    }
}
