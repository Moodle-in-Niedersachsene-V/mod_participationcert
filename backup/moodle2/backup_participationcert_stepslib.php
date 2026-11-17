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
 * Backup structure step for mod_participationcert.
 *
 * @package    mod_participationcert
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Defines the backup structure.
 *
 * Only the activity settings are backed up. Issued certificates are not part of the backup: their
 * codes are unique on the site, and a copied course usually starts a new group of participants.
 * Participants of a restored course receive a new certificate with a new date and code.
 *
 * @package    mod_participationcert
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_participationcert_activity_structure_step extends backup_activity_structure_step {
    /**
     * Defines the structure of participationcert.xml.
     *
     * @return backup_nested_element Root element.
     */
    protected function define_structure() {
        $participationcert = new backup_nested_element('participationcert', ['id'], [
            'name', 'intro', 'introformat', 'certtitle', 'scope', 'topics', 'topicsformat', 'emailcertificate',
            'timecreated', 'timemodified',
        ]);
        $participationcert->set_source_table('participationcert', ['id' => backup::VAR_ACTIVITYID]);
        $participationcert->annotate_files('mod_participationcert', 'intro', null);

        return $this->prepare_activity_structure($participationcert);
    }
}
