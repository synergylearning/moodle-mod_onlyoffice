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
 * Defines backup_onlyoffice_activity_task class
 *
 * @package   mod_onlyoffice
 * @copyright 2019 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/onlyoffice/backup/moodle2/backup_onlyoffice_stepslib.php');
require_once($CFG->dirroot.'/mod/onlyoffice/backup/moodle2/backup_onlyoffice_settingslib.php');

/**
 * Provides the steps to perform one complete backup of the onlyoffice instance
 */
class backup_onlyoffice_activity_task extends backup_activity_task {

    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
    }

    /**
     * Defines a backup step to store the instance data in the onlyoffice.xml file
     */
    protected function define_my_steps() {
        $this->add_step(new backup_onlyoffice_activity_structure_step('onlyoffice structure', 'onlyoffice.xml'));
    }

    /**
     * Encodes URLs to the index.php, view.php and discuss.php scripts
     *
     * @param string $content some HTML text that eventually contains URLs to the activity instance scripts
     * @return string the content with the URLs encoded
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');

        // Link to the list of instances.
        $search = "/(".$base."\/mod\/onlyoffice\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@ONLYOFFICEINDEX*$2@$', $content);

        // Link to instance view by moduleid.
        $search = "/(".$base."\/mod\/onlyoffice\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@ONLYOFFICEVIEWBYID*$2@$', $content);

        return $content;
    }
}
