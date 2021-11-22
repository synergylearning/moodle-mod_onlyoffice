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
 * Define all the backup steps that will be used by the backup_onlyoffice_activity_task
 */

/**
 * Define the complete instance structure for backup, with file and id annotations
 */
class backup_onlyoffice_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $onlyoffice = new backup_nested_element('onlyoffice', ['id'], [
            'name', 'intro', 'introformat', 'timecreated', 'timemodified',
            'format', 'initialtext', 'display', 'width', 'height', 'displayname',
            'displaydescription', 'candownload', 'canprint',
        ]);

        if ($userinfo) {
            $documents = new backup_nested_element('documents');
            $document = new backup_nested_element('document', ['id'], [
                'groupid', 'locked', 'documentkey',
            ]);
        }

        // Build the tree.
        if ($userinfo) {
            $onlyoffice->add_child($documents);
            $documents->add_child($document);
        }

        // Define sources.
        $onlyoffice->set_source_table('onlyoffice', ['id' => backup::VAR_ACTIVITYID]);
        if ($userinfo) {
            $document->set_source_table('onlyoffice_document', ['onlyoffice' => backup::VAR_PARENTID]);
        }

        // Define id annotations.
        if ($userinfo) {
            $document->annotate_ids('group', 'groupid');
        }

        // Define file annotations.
        $onlyoffice->annotate_files('mod_onlyoffice', 'intro', null);
        $onlyoffice->annotate_files('mod_onlyoffice', \mod_onlyoffice\onlyoffice::FILEAREA_INITIAL, null);
        if ($userinfo) {
            $document->annotate_files('mod_onlyoffice', \mod_onlyoffice\onlyoffice::FILEAREA_GROUP, 'groupid');
        }

        // Return the root element (instance), wrapped into standard activity structure.
        return $this->prepare_activity_structure($onlyoffice);
    }

}
