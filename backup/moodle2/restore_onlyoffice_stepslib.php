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
 * @package    mod_onlyoffice
 * @copyright 2019 Davo Smith, Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_onlyoffice_activity_task
 */

use mod_onlyoffice\onlyoffice;

/**
 * Structure step to restore one instance of the activity
 */
class restore_onlyoffice_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('onlyoffice', '/activity/onlyoffice');
        $paths[] = new restore_path_element('onlyoffice_document', '/activity/onlyoffice/documents/document');

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    protected function process_onlyoffice($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();

        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('onlyoffice', $data);
        $this->apply_activity_instance($newitemid);
    }

    protected function process_onlyoffice_document($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $oldgroupid = $data->groupid;

        $data->onlyoffice = $this->get_new_parentid('onlyoffice');
        $data->groupid = $data->groupid ? $this->get_mappingid('group', $data->groupid) : 0;
        $data->documentkey = \mod_onlyoffice\record\onlyoffice_document::generate_document_key();

        $newitemid = $DB->insert_record('onlyoffice_document', $data);
        $this->set_mapping('onlyoffice_document', $oldid, $newitemid);
        $this->set_mapping('onlyoffice_group', $oldgroupid, $data->groupid, true);
    }

    protected function after_execute() {
        // Add OnlyOffice related files.
        $this->add_related_files('mod_onlyoffice', 'intro', null);
        $this->add_related_files('mod_onlyoffice', onlyoffice::FILEAREA_INITIAL, null);
        $this->add_related_files('mod_onlyoffice', onlyoffice::FILEAREA_GROUP, 'onlyoffice_group');
    }
}
