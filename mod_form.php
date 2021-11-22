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
 * Create instance form
 *
 * @package mod_onlyoffice
 * @copyright 2019 Davo Smith, 2020 Alex Paphitis <alex@paphitis.net>, Synergy Learning
 *  based on code from Olumuyiwa Taiwo <muyi.taiwo@logicexpertise.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_onlyoffice\onlyoffice;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/moodleform_mod.php');

class mod_onlyoffice_mod_form extends moodleform_mod {
    /**
     * Form definition
     * @throws coding_exception
     * @throws dml_exception
     */
    protected function definition() {
        $mform = $this->_form;
        $config = get_config('mod_onlyoffice');

        // General section.
        $mform->addElement('header', 'generalheader', get_string('general'));
        $mform->addElement('text', 'name', get_string('name', 'mod_onlyoffice'), ['size' => onlyoffice::ACTIVITY_NAME_LENGTH_MAX]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required');

        $this->standard_intro_elements();

        // Format section.
        $mform->addElement('header', 'formatheader', get_string('format'));
        $mform->addElement('select', 'format', get_string('format', 'mod_onlyoffice'), onlyoffice::get_format_menu());
        $mform->setDefault('format', $config->defaultformat);

        // Format cannot be changed when the instance has been created.
        if ($this->_instance) {
            $mform->freeze('format'); // Instance created, freeze format.
        }

        // For new instances we'll show an empty file picker with no file.
        if (!$this->_instance) {
            $mform->addElement('filemanager', 'initialfile_filemanager', get_string('initialfile',
                'mod_onlyoffice'), null, self::get_filemanager_opts());
            $mform->hideIf('initialfile_filemanager', 'format', 'neq',
                onlyoffice::FORMAT_UPLOAD);
        }

        // Get the file for an instance that already exists and is using the file upload format.
        // Once the file has been uploaded it cannot be changed.
        if ($this->_instance && $this->current->format === onlyoffice::FORMAT_UPLOAD) {
            $mform->addElement('static', 'initialfile', get_string('initialfile', 'mod_onlyoffice'), $this->get_file_link());
        }

        // Add the initial text area when the instance does not already exist or if the format is text.
        if (!$this->_instance || $this->current->format === onlyoffice::FORMAT_TEXT) {
            $mform->addElement('textarea', 'initialtext', get_string('initialtext', 'mod_onlyoffice'));
            $mform->setDefault('initialtext', onlyoffice::get_default_initial_text());
            $mform->hideIf('initialtext', 'format', 'neq', onlyoffice::FORMAT_TEXT);
        }

        // Document permissions section.
        $mform->addElement('header', 'permissionsheader', get_string('permissions', 'mod_onlyoffice'));

        // Can the document be downloaded.
        $mform->addElement('selectyesno', 'candownload', get_string('candownload', 'mod_onlyoffice'));
        $mform->setDefault('candownload', onlyoffice::get_default_can_download());

        // Can the document be printed.
        $mform->addElement('selectyesno', 'canprint', get_string('canprint', 'mod_onlyoffice'));
        $mform->setDefault('canprint', onlyoffice::get_default_can_print());

        // Display section.
        $mform->addElement('header', 'displayheader', get_string('display', 'mod_onlyoffice'));
        $mform->addElement('select', 'display', get_string('display', 'mod_onlyoffice'), onlyoffice::get_display_menu());
        $mform->setDefault('display', $config->defaultdisplay);

        // IFRAME Width.
        $mform->addElement('text', 'width', get_string('width', 'mod_onlyoffice'));
        $mform->setDefault('width', 0);
        $mform->setType('width', PARAM_INT);

        // IFRAME Height.
        $mform->addElement('text', 'height', get_string('height', 'mod_onlyoffice'));
        $mform->setDefault('height', 0);
        $mform->setType('height', PARAM_INT);

        // Display activity name.
        $mform->addElement('selectyesno', 'displayname', get_string('displayname', 'mod_onlyoffice'));
        $mform->setDefault('displayname', $config->defaultdisplayname);

        // Display description.
        $mform->addElement('selectyesno', 'displaydescription', get_string('displaydescription', 'mod_onlyoffice'));
        $mform->setDefault('displaydescription', $config->defaultdisplaydescription);

        // Standard sections.
        $this->standard_coursemodule_elements();

        // Action buttons.
        $this->add_action_buttons();
    }

    /**
     * Form validation
     * @param array $data Form data
     * @param array $files Any files provided
     * @return array Errors in form data
     * @throws coding_exception
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        // Validate for format upload.
        if ($data['format'] === onlyoffice::FORMAT_UPLOAD) {
            return $this->validation_format_upload($errors, $data, $files);
        }

        // Validate for format text.
        if ($data['format'] === onlyoffice::FORMAT_TEXT) {
            return $this->validation_format_text($errors, $data, $files);
        }

        return $errors;
    }

    /**
     * Validation to perform when the format is upload
     * @param array $errors Errors raised so far
     * @param array $data Form data
     * @param array $files Any files provided
     * @return array Any further errors raised during validation
     * @throws coding_exception
     */
    private function validation_format_upload(array $errors, array $data, array $files): array {
        // File must be provided.
        if (empty($data['initialfile_filemanager'])) {
            // File not provided.
            $errors['initialfile_filemanager'] = get_string('requiredforupload', 'mod_onlyoffice');
            return $errors;
        }

        // File has been provided.
        $info = file_get_draft_area_info($data['initialfile_filemanager']);
        if (!$info['filecount']) {
            $errors['initialfile_filemanager'] = get_string('requiredforupload', 'mod_onlyoffice');
        }

        return $errors;
    }

    /**
     * Validation to perform when the format is text
     * @param array $errors Errors raised so far
     * @param array $data Form data
     * @param array $files Any files provided
     * @return array Any further errors raised during validation
     * @throws coding_exception
     */
    private function validation_format_text(array $errors, array $data, array $files): array {
        // Text must be provided.
        if (!isset($data['initialtext']) || !trim($data['initialtext'])) {
            $errors['initialtext'] = get_string('requiredfortext', 'mod_onlyoffice');
        }

        return $errors;
    }

    /**
     * Get file manager options
     * @return array File manager options
     */
    public static function get_filemanager_opts(): array {
        return [
            'subdirs' => 0,
            'maxbytes' => 0,
            'maxfiles' => 1,
            'accepted_types' => onlyoffice::get_accepted_types(),
        ];
    }

    /**
     * Get the file link
     * @return string Link to the file
     * @throws coding_exception
     */
    private function get_file_link(): string {
        $fs = get_file_storage();

        // Get all the files.
        $files = $fs->get_area_files(
            $this->context->id,
            'mod_onlyoffice',
            onlyoffice::FILEAREA_INITIAL,
            false,
            '',
            false,
            0,
            0,
            1
        );

        // Try get the first file.
        if (!$file = reset($files)) {
            return get_string('missingfile', 'mod_onlyoffice'); // File doesn't exist.
        }

        // Build the URL.
        $url = moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename(),
            true
        );

        return html_writer::link($url, $file->get_filename());
    }
}
