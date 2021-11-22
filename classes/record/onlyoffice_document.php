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
 * OnlyOffice document
 *
 * @package mod_onlyoffice
 * @author Alex Paphitis <alex@paphitis.net> based on code from Olumuyiwa Taiwo <muyi.taiwo@logicexpertise.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_onlyoffice\record;

use cm_info;
use coding_exception;
use context;
use context_system;
use dml_exception;
use file_exception;
use mod_onlyoffice\event\document_locked;
use mod_onlyoffice\event\document_unlocked;
use mod_onlyoffice\onlyoffice;
use mod_onlyoffice\util\crypt;
use moodle_exception;
use moodle_url;
use stdClass;
use stored_file;
use stored_file_creation_exception;

defined('MOODLE_INTERNAL') || die();

class onlyoffice_document {
    /** @var int Length of the document key */
    const DOCUMENT_KEY_LENGTH = 20;

    /** @var cm_info $cm Course module */
    public $cm;

    /** @var context $context Context */
    public $context;

    /** @var int $groupid Group ID */
    public $groupid;

    /** @var stdClass $activityrecord Activity instance record */
    private $activityrecord;

    /** @var stdClass $documentrecord Document record */
    private $documentrecord;

    /** @var stdClass $user User record */
    private $user;

    /** @var stored_file $file File */
    public $file;

    /**
     * Constructor
     * @param cm_info|int $cmorid Course module itself or the course module ID
     * @param int $groupid Group ID
     * @param stdClass|null $user User object
     * @throws dml_exception|coding_exception
     */
    public function __construct($cmorid, int $groupid, stdClass $user = null) {
        global $DB, $USER;

        // Either course module or the ID for the course module has to be provided.
        if (is_numeric($cmorid)) {
            $this->cm = cm_info::create(get_coursemodule_from_instance('onlyoffice', $cmorid));
        } else {
            $this->cm = $cmorid;
        }

        $this->context = $this->cm->context;
        $this->groupid = $groupid;
        $this->user = $user ?? $USER; // Default to current user if not provided.

        // Activity instance and document records.
        $this->activityrecord = $DB->get_record('onlyoffice', ['id' => $this->cm->instance], '*', MUST_EXIST);
        $this->documentrecord = $this->load_document();
        $this->file = $this->load_file();
    }

    /**
     * Load the document record for this activity instance and group
     * @return stdClass Document server
     * @throws dml_exception
     */
    private function load_document(): stdClass {
        global $DB;

        // Try get the existing record, otherwise create it.
        $params = ['onlyoffice' => $this->activityrecord->id, 'groupid' => $this->groupid];
        if (!$record = $DB->get_record('onlyoffice_document', $params)) {
            return $this->create_document(); // Doesn't exist, create it.
        }

        return $record;
    }

    /**
     * Create the individual document record
     * @return stdClass Document record
     * @throws dml_exception
     */
    private function create_document(): stdClass {
        global $DB;

        $record = (object)[
            'onlyoffice' => $this->activityrecord->id,
            'groupid' => $this->groupid,
            'documentkey' => self::generate_document_key(),
            'locked' => onlyoffice::LOCKED_DEFAULT,
        ];

        $record->id = $DB->insert_record('onlyoffice_document', $record);
        return $record;
    }

    /**
     * Update the current document key
     * @param string|null $key Current key for the document
     * @throws dml_exception
     */
    private function update_document_key(string $key = null): void {
        global $DB;
        $key = $key ?? self::generate_document_key();
        $DB->set_field('onlyoffice_document', 'documentkey', $key, ['id' => $this->documentrecord->id]);
    }

    /**
     * Generate a document key
     * @return string New document key
     */
    public static function generate_document_key(): string {
        return random_string(self::DOCUMENT_KEY_LENGTH);
    }

    /**
     * Load the file for this document
     * @return stored_file File tied to the document
     * @throws coding_exception
     * @throws dml_exception
     * @throws file_exception
     * @throws moodle_exception
     * @throws stored_file_creation_exception
     */
    private function load_file(): stored_file {
        $fs = get_file_storage();

        // Get the first file.
        $files = $fs->get_area_files($this->context->id, 'mod_onlyoffice', onlyoffice::FILEAREA_GROUP,
            $this->groupid, '', false, 0, 0, 1);

        // Check whether the file exists.
        if (!$file = reset($files)) {
            return $this->create_file(); // File doesn't exist, create it.
        }

        return $file;
    }

    /**
     * Update the current file
     * @param stored_file $newfiletemp Current temporary file to use for overwriting the current file
     * @throws coding_exception|dml_exception
     */
    public function update_file(stored_file $newfiletemp): void {
        // Replace the current file with the new one and delete the temporary file.
        $this->file->replace_file_with($newfiletemp);
        $this->file->set_timemodified(time());
        $newfiletemp->delete();
        $this->update_document_key();
    }

    /**
     * Create the file depending on the format
     * @return stored_file New file to store
     * @throws coding_exception
     * @throws dml_exception
     * @throws file_exception
     * @throws moodle_exception
     * @throws stored_file_creation_exception
     */
    private function create_file(): stored_file {
        $format = $this->activityrecord->format;

        switch ($format) {
            case onlyoffice::FORMAT_UPLOAD:
                return $this->create_file_initial_upload();
            case onlyoffice::FORMAT_TEXT:
                return $this->create_file_initial_text();
            case onlyoffice::FORMAT_PRESENTATION:
            case onlyoffice::FORMAT_WORDPROCESSOR:
            case onlyoffice::FORMAT_SPREADSHEET:
                return $this->create_file_from_template();
            default:
                throw new coding_exception("Unknown format: {$format}");
        }
    }

    /**
     * Get the file template we're using
     * @return stored_file File template
     * @throws coding_exception
     * @throws dml_exception
     * @throws file_exception
     * @throws stored_file_creation_exception
     */
    private function create_file_from_template(): stored_file {
        global $CFG;

        $templatesitemids = [
            onlyoffice::FORMAT_SPREADSHEET => ['itemid' => onlyoffice::FORMAT_SPREADSHEET_ITEM_ID, 'ext' => 'xlsx'],
            onlyoffice::FORMAT_WORDPROCESSOR => ['itemid' => onlyoffice::FORMAT_WORDPROCESSOR_ITEM_ID, 'ext' => 'docx'],
            onlyoffice::FORMAT_PRESENTATION => ['itemid' => onlyoffice::FORMAT_PRESENTATION_ITEM_ID, 'ext' => 'pptx'],
        ];

        // Template ID and extension for the given format.
        $format = $this->activityrecord->format;
        $itemid = $templatesitemids[$format]['itemid'];
        $ext = $templatesitemids[$format]['ext'];

        // Try get get the overridden template file.
        $fs = get_file_storage();
        $ctx = context_system::instance();
        $files = $fs->get_area_files($ctx->id, 'mod_onlyoffice', onlyoffice::FILEAREA_TEMPLATES,
            $itemid, '', false, 0, 0, 1);

        // Check whether we have an overridden template file we can use instead of the blank file.
        if ($templatefile = reset($files)) {
            // We have a template file, we'll make a copy of it.
            $filerec = $this->build_file_record();
            $filerec->filename = clean_filename(format_string("{$this->activityrecord->name}.{$ext}"));
            $file = $fs->create_file_from_storedfile($filerec, $templatefile);
            return $file; // We have an overridden template file.
        }

        // No overridden template, we'll create a copy of the blank file for this format.
        $filerec = $this->build_file_record();
        $filerec->filename = clean_filename(format_string("{$this->activityrecord->name}.{$ext}"));
        $filepath = "{$CFG->dirroot}/mod/onlyoffice/blankfiles/blank{$format}.{$ext}";
        $file = $fs->create_file_from_pathname($filerec, $filepath);

        return $file;
    }

    /**
     * Build the initial file record
     * @return stdClass File record
     */
    private function build_file_record(): stdClass {
        // Build the record.
        $filerec = (object)[
            'contextid' => $this->context->id,
            'component' => 'mod_onlyoffice',
            'filearea' => onlyoffice::FILEAREA_GROUP,
            'itemid' => $this->groupid,
            'filepath' => '/',
            'filename' => clean_filename(format_string($this->activityrecord->name)),
        ];

        return $filerec;
    }

    /**
     * Create a file from initial text
     * @return stored_file File created from the initial text
     * @throws file_exception
     * @throws stored_file_creation_exception
     */
    private function create_file_initial_text(): stored_file {
        $fs = get_file_storage();

        $content = $this->activityrecord->initialtext;
        $filerec = $this->build_file_record();
        $filerec->filename = "{$filerec->filename}.txt";

        return $fs->create_file_from_string($filerec, $content);

    }

    /**
     * Create a file from an initial upload
     * @return stored_file File created from the initial upload
     * @throws file_exception
     * @throws stored_file_creation_exception
     * @throws coding_exception
     * @throws moodle_exception
     */
    private function create_file_initial_upload(): stored_file {
        $fs = get_file_storage();

        // Build the file.
        $filerec = $this->build_file_record();
        $initfile = $this->get_initial_file();
        $ext = pathinfo($initfile->get_filename(), PATHINFO_EXTENSION);
        $filerec->filename = "{$filerec->filename}.$ext";

        // Create the file from our stored file.
        $file = $fs->create_file_from_storedfile($filerec, $initfile);
        return $file;
    }

    /**
     * Get the initial file
     * @return stored_file Initial file
     * @throws coding_exception
     * @throws moodle_exception
     */
    private function get_initial_file() {
        $fs = get_file_storage();
        $files = $fs->get_area_files($this->context->id, 'mod_onlyoffice', onlyoffice::FILEAREA_INITIAL,
            false, '', false, 0, 0, 1);

        // Initial file must exist.
        if (!$file = reset($files)) {
            throw new moodle_exception('initialfilemissing', 'mod_onlyoffice');
        }

        // File exists.
        return $file;
    }

    /**
     * Whether or not the user can lock or unlock this document
     * @return bool Whether or not the user can lock or unlock the document
     * @throws coding_exception
     */
    public function can_lock_unlock(): bool {
        return has_capability('mod/onlyoffice:lock', $this->context, $this->user);
    }

    /**
     * Is this document locked
     * @return bool Whether or not the document is locked
     */
    public function is_locked(): bool {
        // Document lock status.
        $documentlocked = (bool) $this->documentrecord->locked;
        return $documentlocked;
    }

    /**
     * Can the user edit locked documents?
     * @return bool Whether or not the user can edit the document (Based only on capability)
     * @throws coding_exception
     */
    public function can_user_edit_locked(): bool {
        return has_capability('mod/onlyoffice:editlocked', $this->context, $this->user);
    }

    /**
     * Is this document locked to the user?
     * @return bool Whether or not the document is locked to this user
     * @throws coding_exception
     */
    public function is_locked_to_user(): bool {
        // Check whether user can edit locked documents.
        if ($this->can_user_edit_locked()) {
            return false; // User can edit even if this document is locked => not locked to this user.
        }

        // User must be in the group.
        $usersgroups = groups_get_all_groups($this->cm->course, $this->user->id);
        $usersgroupids = array_column($usersgroups, 'id');
        $usernotingroup = $this->groupid && !in_array($this->groupid, $usersgroupids);

        // Document lock status.
        $documentlocked = (bool) $this->documentrecord->locked;

        return $documentlocked || $usernotingroup;
    }

    /**
     * Set the lock status of this document
     * @param bool $shouldlock Whether or not to lock the document
     * @throws coding_exception
     * @throws dml_exception
     */
    public function set_locked(bool $shouldlock): void {
        global $DB;

        // Check whether user can change the lock status of this document.
        if (!$this->can_lock_unlock()) {
            return; // User cannot change the lock status.
        }

        // Update the lock status of the document.
        $DB->set_field('onlyoffice_document', 'locked', $shouldlock, ['id' => $this->documentrecord->id]);
        $this->documentrecord->locked = $shouldlock;

        // Trigger either the document locked or the document unlocked event.
        if ($shouldlock) {
            document_locked::trigger_from_document($this->cm->instance, $this->documentrecord);
            return;
        }

        // Otherwise trigger the document unlocked event.
        document_unlocked::trigger_from_document($this->cm->instance, $this->documentrecord);
    }

    /**
     * Whether or not to display the name of this document
     * @return bool Whether or not to display the name of this document
     */
    public function should_display_name(): bool {
        return (bool) $this->activityrecord->displayname;
    }

    /**
     * Whether or not to display the description
     * @return bool Whether or not to display the description for this document
     */
    public function should_display_description(): bool {
        return (bool) $this->activityrecord->displaydescription;
    }

    /**
     * Get the permissions for this document for the user
     * @return bool[] Array of permissions for this document
     * @throws coding_exception
     */
    public function get_permissions(): array {
        return [
            'edit' => !$this->is_locked_to_user(),
            'print' => $this->can_print(),
            'download' => $this->can_download(),
        ];
    }

    /**
     * Get the key for this document
     * @return string Key for this document
     */
    public function get_key(): string {
        return $this->documentrecord->documentkey;
    }

    /**
     * Can the document be printed?
     * @return bool
     */
    public function can_print(): bool {
        return (bool) $this->activityrecord->canprint;
    }

    /**
     * Can the document be downloaded?
     * @return bool Whether or not the document file can be downloaded
     */
    public function can_download(): bool {
        return (bool) $this->activityrecord->candownload;
    }

    /**
     * Get the external URL to download the file
     * @return moodle_url URL accessible externally to download the URL
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function get_external_download_url(): moodle_url {
        global $USER;

        // File metadata.
        $filename = $this->file->get_filename();
        $filepath = $this->file->get_filepath();
        $itemid = $this->groupid;

        // Document URL.
        $params = ['userid' => $USER->id, 'cmid' => $this->cm->instance, 'groupid' => $this->groupid];
        $params = crypt::encode_and_sign($params);
        $url = "/pluginfile.php/{$this->context->id}/mod_onlyoffice/group/{$itemid}{$filepath}{$filename}";

        $documenturl = new moodle_url($url, ['doc' => $params]);
        return $documenturl;
    }
}
