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
 * Activity settings form for mod_participationcert.
 *
 * @package    mod_participationcert
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Activity settings form for mod_participationcert.
 *
 * @package    mod_participationcert
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_participationcert_mod_form extends moodleform_mod {
    /**
     * Defines the form fields.
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $this->standard_intro_elements();

        $mform->addElement('header', 'certificatesettings', get_string('certificatesettings', 'participationcert'));
        $mform->setExpanded('certificatesettings');
        $mform->addElement('static', 'availabilitynote', '', get_string('availabilitynote', 'participationcert'));

        $mform->addElement('text', 'certtitle', get_string('certtitle', 'participationcert'), ['size' => '64']);
        $mform->setType('certtitle', PARAM_TEXT);
        $mform->setDefault('certtitle', get_string('defaultcerttitle', 'participationcert'));
        $mform->addRule('certtitle', null, 'required', null, 'client');
        $mform->addRule('certtitle', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('certtitle', 'certtitle', 'participationcert');

        $mform->addElement('text', 'scope', get_string('scope', 'participationcert'), ['size' => '64']);
        $mform->setType('scope', PARAM_TEXT);
        $mform->addRule('scope', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('scope', 'scope', 'participationcert');

        $mform->addElement(
            'editor',
            'topicseditor',
            get_string('topics', 'participationcert'),
            ['rows' => 20],
            ['maxfiles' => 0, 'context' => $this->context]
        );
        $mform->setType('topicseditor', PARAM_RAW);
        $mform->addHelpButton('topicseditor', 'topics', 'participationcert');

        $mform->addElement('selectyesno', 'emailcertificate', get_string('emailcertificate', 'participationcert'));
        $mform->setDefault('emailcertificate', 0);
        $mform->addHelpButton('emailcertificate', 'emailcertificate', 'participationcert');

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Loads the course contents into the editor field.
     *
     * @param array $defaultvalues Form defaults, passed by reference.
     * @return void
     */
    public function data_preprocessing(&$defaultvalues) {
        parent::data_preprocessing($defaultvalues);
        $defaultvalues['topicseditor'] = [
            'text' => $defaultvalues['topics'] ?? '',
            'format' => $defaultvalues['topicsformat'] ?? FORMAT_HTML,
        ];
    }

    /**
     * Rejects a title that consists of spaces only.
     *
     * @param array $data Submitted data.
     * @param array $files Uploaded files.
     * @return array Errors by field name.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (trim($data['certtitle'] ?? '') === '') {
            $errors['certtitle'] = get_string('required');
        }
        return $errors;
    }
}
