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
 * Restore structure step for mod_participationcert.
 *
 * @package    mod_participationcert
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Restores the activity settings. Issued certificates are not part of the backup.
 *
 * @package    mod_participationcert
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_participationcert_activity_structure_step extends restore_activity_structure_step {
    /**
     * Defines the restore paths.
     *
     * @return restore_path_element[] Paths.
     */
    protected function define_structure() {
        $paths = [new restore_path_element('participationcert', '/activity/participationcert')];
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restores the instance.
     *
     * The insert runs inside the restore controller, which checks the restore capabilities; it is
     * not reached by a form of this plugin, so no session key check applies.
     *
     * @param array $data Element data.
     * @return void
     */
    protected function process_participationcert($data) {
        global $DB;

        $data = (object) $data;
        $data->course = $this->get_courseid();
        // phpcs:ignore moodle.Commenting.InlineComment
        $newid = $DB->insert_record('participationcert', $data); // nosemgrep: moodle-fehlender-sesskey
        $this->apply_activity_instance($newid);
    }

    /**
     * Restores the intro files.
     *
     * @return void
     */
    protected function after_execute() {
        $this->add_related_files('mod_participationcert', 'intro', null);
    }
}
