<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Library functions
 *
 * @package mod_onlyoffice
 * @copyright 2019 Davo Smith, 2020 Alex Paphitis <alex@paphitis.net>, Synergy Learning
 *  based on code from Olumuyiwa Taiwo <muyi.taiwo@logicexpertise.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_completion\api;
use mod_onlyoffice\onlyoffice;
use mod_onlyoffice\util\crypt;

defined('MOODLE_INTERNAL') || die();

/**
 * List of features supported
 * @param string $feature FEATURE_xx constant for requested feature
 * @return boolean|null True if module supports feature, false if not, null if doesn't know
 */
function onlyoffice_supports(string $feature) {
    switch ($feature) {
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_BACKUP_MOODLE2:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
        case FEATURE_MOD_INTRO:
        case FEATURE_GROUPINGS:
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GRADE_OUTCOMES:
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        default:
            return null;
    }
}

/**
 * Add a new instance of this activity
 * @param $rec
 * @param mod_onlyoffice_mod_form $mform
 * @return int
 * @throws dml_exception
 */
function onlyoffice_add_instance(stdClass $rec, mod_onlyoffice_mod_form $mform = null): int {
    global $CFG, $DB;
    require_once("$CFG->dirroot/mod/onlyoffice/mod_form.php");

    // Save to the database.
    $rec->timecreated = time();
    $rec->timemodified = time();
    $rec->id = $DB->insert_record('onlyoffice', $rec);

    // Update completion.
    $completiontimeexpected = !empty($rec->completionexpected) ? $rec->completionexpected : null;
    api::update_completion_date_event($rec->coursemodule, 'onlyoffice', $rec->id, $completiontimeexpected);

    // Save the file.
    $context = context_module::instance($rec->coursemodule);
    file_postupdate_standard_filemanager($rec, 'initialfile', mod_onlyoffice_mod_form::get_filemanager_opts(), $context,
        'mod_onlyoffice', onlyoffice::FILEAREA_INITIAL, 0);

    return $rec->id;

}

/**
 * Update an existing activity instance
 * @param stdClass $rec
 * @return bool
 * @throws dml_exception
 */
function onlyoffice_update_instance(stdClass $rec): bool {
    global $DB;

    // Update the database record.
    $rec->id = $rec->instance;
    $rec->timemodified = time();
    $DB->update_record('onlyoffice', $rec);

    // Update completion.
    $completiontimeexpected = !empty($rec->completionexpected) ? $rec->completionexpected : null;
    api::update_completion_date_event($rec->coursemodule, 'onlyoffice', $rec->id, $completiontimeexpected);

    // Do not save the 'initial file' here, as you cannot change this after the activity has been created.

    return true;
}

/**
 * Delete an instance of the activity
 * @param int $id Activity ID
 * @return bool
 * @throws coding_exception
 * @throws dml_exception
 */
function onlyoffice_delete_instance(int $id): bool {
    global $DB;

    // Delete the record from the database if it exists.
    if (!$rec = $DB->get_record('onlyoffice', ['id' => $id])) {
        return false; // Record doesn't exist.
    }

    // Update completion.
    $cm = get_coursemodule_from_instance('onlyoffice', $id);
    api::update_completion_date_event($cm->id, 'onlyoffice', $id, null);

    // Delete related records.
    $DB->delete_records('onlyoffice_document', ['onlyoffice' => $rec->id]);
    $DB->delete_records('onlyoffice', ['id' => $rec->id]);

    return true;
}

/**
 * Add extra information when printing this activity in a course listing
 * @param stdClass $coursemodule Course module
 * @return cached_cm_info|null
 * @throws dml_exception
 * @throws moodle_exception
 */
function onlyoffice_get_coursemodule_info($coursemodule) {
    global $DB;

    // Check whether the activity instance exists.
    if (!$rec = $DB->get_record('onlyoffice', ['id' => $coursemodule->instance])) {
        return null; // Instance doesn't exist.
    }

    $info = new cached_cm_info();

    // Handle whether to display in a new tab.
    if ($rec->display === onlyoffice::DISPLAY_NEW) {
        // Use javascript to open the link in a new tab.
        $url = new moodle_url('/mod/onlyoffice/view.php', ['id' => $coursemodule->id]);
        $urlstr = $url->out(false);
        $info->onclick = "event.preventDefault(); window.open('$urlstr', '_blank').focus();";
    }

    // Handle whether to show the description.
    if ($coursemodule->showdescription) {
        $info->content = format_module_intro('onlyoffice', $rec, $coursemodule->id, false);
    }

    return $info;
}

/**
 * Handle file serving
 * @param $course
 * @param $cm
 * @param $context
 * @param $filearea
 * @param $args
 * @param $forcedownload
 * @param array $options
 * @throws coding_exception
 * @throws moodle_exception
 * @throws require_login_exception
 */
function onlyoffice_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    // Must be in a course module context.
    if ($context->contextlevel != CONTEXT_MODULE) {
        return; // Not in a course mdoule context.
    }

    // User must provide a token or must be logged in.
    if ($documentjson = optional_param('doc', '', PARAM_TEXT)) {
        $usingtoken = true;
        $decoded = crypt::decode($documentjson);
        $userid = $decoded->userid;
    } else {
        $usingtoken = false;
        require_login($course, false, $cm); // Otherwise user must be logged in.
    }

    // File link only occurs on the edit settings page, so restrict access to teachers.
    if (!$usingtoken && !has_capability('moodle/course:manageactivities', $context, $userid)) {
        return; // User does not have the capability.
    }

    // Must be a file area.
    if (!in_array($filearea, onlyoffice::FILEAREAS)) {
        return; // Not a file area.
    }

    // Item ID is the group ID.
    $itemid = (int) array_shift($args);
    if ($itemid < 0) {
        return; // Invalid group ID.
    }

    $filename = array_pop($args);
    $filepath = '/' . implode('/', $args);
    if ($filepath !== '/') {
        $filepath .= '/';
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_onlyoffice', $filearea, $itemid, $filepath, $filename);

    // Check file exists.
    if (!$file) {
        return; // File does not exist.
    }

    // We'll have to force downloading if using a token for OnlyOffice to be able to grab the file.
    if ($usingtoken) {
        $forcedownload = true;
    }

    // Send back the file.
    send_stored_file($file, null, 0, $forcedownload, $options);
}

/**
 * Register the ability to handle drag and drop file uploads
 * @return array containing details of the files / types the mod can handle
 * @throws coding_exception
 */
function onlyoffice_dndupload_register(): array {
    $extensions = onlyoffice::get_accepted_types();
    $strdnd = get_string('dnduploaddocument', 'mod_onlyoffice');
    $files = [];

    foreach ($extensions as $extn) {
        $extn = trim($extn, '.');
        $files[] = ['extension' => $extn, 'message' => $strdnd];
    }

    return ['files' => $files];
}

/**
 * Handle a file that has been uploaded
 * @param object $uploadinfo details of the file / content that has been uploaded
 * @return int instance id of the newly created mod
 * @throws dml_exception
 */
function onlyoffice_dndupload_handle($uploadinfo) {
    // Gather the required info.
    $data = new stdClass();

    $data->course = $uploadinfo->course->id;
    $data->name = $uploadinfo->displayname;
    $data->intro = '';
    $data->introformat = FORMAT_HTML;
    $data->coursemodule = $uploadinfo->coursemodule;
    $data->initialfile_filemanager = $uploadinfo->draftitemid;
    $data->format = onlyoffice::FORMAT_UPLOAD;

    // Set the display options to the site defaults.
    $config = get_config('mod_onlyoffice');

    $data->display = $config->defaultdisplay;
    $data->displayname = $config->defaultdisplayname;
    $data->displaydescription = $config->defaultdisplaydescription;
    $data->width = 0;
    $data->height = 0;

    return onlyoffice_add_instance($data, null);
}