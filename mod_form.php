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
 * Mod_form file
 *
 * @package     mod_apidocs
 * @author      Felix Manrique / Mr Jacket
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_apidocs_mod_form extends moodleform_mod {
    public function definition() {
        $mform = $this->_form;
        $mform->addElement('text', 'name', get_string('name', 'mod_apidocs'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addElement('hidden', 'intro', ''); 
        $mform->setType('intro', PARAM_RAW);
        $mform->addElement('hidden', 'introformat', FORMAT_HTML);
        $mform->setType('introformat', PARAM_INT);

        $filemanopts = [
            'subdirs' => 0,
            'maxbytes' => 1073741824, 
            'maxfiles' => 1, 
            'accepted_types' => ['*.yaml', '*.yml', '*.json', '*.md']
        ];

        $mform->addElement('filemanager', 'specfiles', get_string('specfiles', 'mod_apidocs'), null, $filemanopts);
        $mform->addHelpButton('specfiles', 'specfiles', 'mod_apidocs');
        $mform->addRule('specfiles', get_string('requiredspecfile', 'mod_apidocs'), 'required', null, 'client');

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (empty($files['specfiles']) && empty($data['specfiles'])) {
            $errors['specfiles'] = get_string('requiredspecfile', 'mod_apidocs');
        }
        return $errors;
    }
}