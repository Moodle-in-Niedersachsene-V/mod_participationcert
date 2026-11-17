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
 * Data generator for mod_participationcert.
 *
 * @package    mod_participationcert
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_participationcert_generator extends testing_module_generator {
    /**
     * Creates a certificate activity with a title, a scope and contents unless given.
     *
     * @param array|stdClass|null $record Instance fields.
     * @param array|null $options Course module options.
     * @return stdClass Instance record with cmid.
     */
    public function create_instance($record = null, ?array $options = null) {
        $record = (array) ($record ?? []);
        $record += [
            'certtitle' => 'Teilnahmebescheinigung',
            'scope' => '12 Unterrichtsstunden',
            'topicseditor' => ['text' => '<ul><li>Grundlagen</li><li>Übungen</li></ul>', 'format' => FORMAT_HTML],
            'emailcertificate' => 0,
        ];
        return parent::create_instance($record, $options);
    }
}
