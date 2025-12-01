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
 * lib archive
 *
 * @package     mod_apidocs
 * @author      Felix Manrique / Mr Jacket
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

function apidocs_add_instance($data, $mform) {
    global $DB;
    if (!isset($data->intro)) $data->intro = '';
    if (!isset($data->introformat)) $data->introformat = FORMAT_HTML;

    $data->timecreated = time();
    $data->timemodified = time();
    
    $id = $DB->insert_record('apidocs', $data);
    $context = context_module::instance($data->coursemodule);
    
    if (!empty($data->specfiles)) {
        file_save_draft_area_files($data->specfiles, $context->id, 'mod_apidocs', 'spec', 0, [
            'subdirs' => 0, 'maxbytes' => 1073741824, 'accepted_types' => ['*.yaml','*.yml','*.json','*.md']
        ]);
    }
    return $id;
}

function apidocs_update_instance($data, $mform) {
    global $DB;
    if (!isset($data->intro)) $data->intro = '';
    if (!isset($data->introformat)) $data->introformat = FORMAT_HTML;

    $data->timemodified = time();
    $data->id = $data->instance;
    $DB->update_record('apidocs', $data);
    
    $context = context_module::instance($data->coursemodule);
    if (isset($data->specfiles)) {
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_apidocs', 'spec');
        
        file_save_draft_area_files($data->specfiles, $context->id, 'mod_apidocs', 'spec', 0, [
            'subdirs' => 0, 'maxbytes' => 1073741824, 'accepted_types' => ['*.yaml','*.yml','*.json','*.md']
        ]);
    }
    return true;
}

function apidocs_delete_instance($id) {
    global $DB;
    if (!$instance = $DB->get_record('apidocs', array('id'=>$id))) return false;
    $cm = get_coursemodule_from_instance('apidocs', $instance->id);
    $context = context_module::instance($cm->id);
    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'mod_apidocs', 'spec');
    $DB->delete_records('apidocs', array('id'=>$instance->id));
    return true;
}

function apidocs_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    if ($context->contextlevel != CONTEXT_MODULE) return false;
    require_login($course, true, $cm);

    if ($filearea !== 'spec') return false;

    $fs = get_file_storage();
    $itemid = (int)array_shift($args);
    $filename = array_pop($args);
    
    if (!$args) { $filepath = '/'; } else { $filepath = '/'.implode('/', $args).'/'; }

    $file = $fs->get_file($context->id, 'mod_apidocs', $filearea, $itemid, $filepath, $filename);

    if (!$file) return false;

    send_stored_file($file, 0, 0, true, $options);
}