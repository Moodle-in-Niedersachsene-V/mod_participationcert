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

namespace mod_participationcert\completion;

use core_completion\activity_custom_completion;

/**
 * Custom completion rule of the certificate activity: the certificate has been issued to the user.
 *
 * @package    mod_participationcert
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {
    /**
     * Returns the completion state of a rule for the user.
     *
     * @param string $rule Rule name.
     * @return int COMPLETION_COMPLETE when a certificate was issued to the user, else COMPLETION_INCOMPLETE.
     */
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);
        $issued = $DB->record_exists(
            'participationcert_issues',
            ['participationcertid' => $this->cm->instance, 'userid' => $this->userid]
        );
        return $issued ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Returns the custom rules of this activity.
     *
     * @return string[] Rule names.
     */
    public static function get_defined_custom_rules(): array {
        return ['completionissued'];
    }

    /**
     * Returns the descriptions of the custom rules shown to participants.
     *
     * @return string[] Descriptions by rule name.
     */
    public function get_custom_rule_descriptions(): array {
        return ['completionissued' => get_string('completiondetail:issued', 'participationcert')];
    }

    /**
     * Returns the order in which the completion rules are shown.
     *
     * @return string[] Rule names.
     */
    public function get_sort_order(): array {
        return ['completionissued'];
    }
}
