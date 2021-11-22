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
 * View a document
 *
 * @package mod_onlyoffice
 * @author Alex Paphitis <alex@paphitis.net> based on code from Olumuyiwa Taiwo <muyi.taiwo@logicexpertise.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\output\notification;
use mod_onlyoffice\editor;
use mod_onlyoffice\event\course_module_viewed;
use mod_onlyoffice\onlyoffice;
use mod_onlyoffice\record\onlyoffice_document;
use mod_onlyoffice\util\view;

require_once(__DIR__.'/../../config.php');
global $PAGE, $DB, $USER, $OUTPUT;

// Param for if we're confirming to download.
$documentserveronline = onlyoffice::is_server_online();
$confirmingdownload = optional_param('download', 0, PARAM_INT);
$confirmed = optional_param('confirm', 0, PARAM_INT);

// Course module ID is required.
$cmid = required_param('id', PARAM_INT);
list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'onlyoffice');
$rec = $DB->get_record('onlyoffice', ['id' => $cm->instance], '*', MUST_EXIST);

// Context.
$context = CONTEXT_MODULE::instance($cm->id);
$PAGE->set_context($context);

// Begin setting up the page.
$PAGE->set_title($rec->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_url('/mod/onlyoffice/view.php', ['id' => $cm->id]);

// User must be logged in and have the capability to view documents.
require_login($course, false, $cm);
require_capability('mod/onlyoffice:view', $context);

// Handle display mode.
if ($rec->display === onlyoffice::DISPLAY_NEW) {
    $PAGE->set_pagelayout('popup');
}

// Trigger course module viewed event.
course_module_viewed::trigger_from_course_cm($course, $cm, $rec);

// Completion - mark module as viewed.
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

// Get the document within the scope of groups.
$viewutil = new view($cm);
$groupid = $viewutil->get_group_id();
$document = new onlyoffice_document($cm, $groupid);

// Process whether to lock the document or not.
$action = optional_param('action', null, PARAM_TEXT);

if (($action === 'lock' || $action === 'unlock') && $document->can_lock_unlock()) {
    require_sesskey();
    $document->set_locked($action === 'lock');
}

// Must provide document server URL and secret.
if (!$documentserverurl = onlyoffice::get_server_url()) {
    print_error('nodocumentserverurl', 'mod_onlyoffice');
}

// Check whether the download has been confirmed (Only in offline mode).
if (!$documentserveronline && $confirmingdownload && $confirmed) {
    $downloadurl = $document->get_external_download_url();
    redirect($downloadurl);
}

// Output the page.
echo $OUTPUT->header();

// Check whether or not to display the activity name.
if ($document->should_display_name()) {
    echo $OUTPUT->heading(format_string($rec->name));
}

// Check whether or not to display the course module description.
if ($document->should_display_description() && trim(strip_tags($rec->intro))) {
    echo $OUTPUT->box(format_module_intro('onlyoffice', $rec, $cm->id), 'generalbox', 'intro');
}

// Check whether to display groups selector (Have to have groups).
if (!$confirmingdownload && $groupid) {
    groups_print_activity_menu($cm, $PAGE->url, false, true);
}

// Notify the user if the document server is offline.
if (!$documentserveronline) {
    $viewutil->handle_offline_mode();
    echo $OUTPUT->footer();
    die();
}

// Lock icon and text.
echo $viewutil->get_lock_icon($document);

// Handle user being able to override locked editing.
if ($document->can_user_edit_locked()) {
    $notification = new \core\output\notification(get_string('lockoverridden', 'mod_onlyoffice'), notification::NOTIFY_INFO);
    $notification->set_show_closebutton(false);
    echo $OUTPUT->render($notification);
}

// Handle container height.
$width = $rec->width > 0 ? "{$rec->width}px" : '100%';
$height = $rec->height > 0 ? "{$rec->height}px" : '100vh'; // Whether to use fixed height given or height of viewport.

// Document container.
echo html_writer::start_div('onlyoffice-container', ['style' => "width: $width; height: $height"]);
echo html_writer::div('', '', ['id' => 'onlyoffice-editor']); // This gets replaced with the iframe.
echo html_writer::end_div();

// Hidden input (Document config).
$editor = new editor($document);
$configstr = json_encode($editor->get_config());
echo html_writer::tag('input', '', ['type' => 'hidden', 'name' => 'config', 'value' => $configstr]);

// Javascript required.
$jsurl = "{$documentserverurl}/web-apps/apps/api/documents/api.js";
echo html_writer::tag('script', '', ['type' => 'text/javascript', 'src' => $jsurl]);
$PAGE->requires->js_call_amd('mod_onlyoffice/editor', 'init', []);

echo $OUTPUT->footer();
