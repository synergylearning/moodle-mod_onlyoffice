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
 * Utility functions for view page
 *
 * @package mod_onlyoffice
 * @author Alex Paphitis <alex@paphitis.net> based on code from Olumuyiwa Taiwo <muyi.taiwo@logicexpertise.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_onlyoffice\util;

use cm_info;
use coding_exception;
use core\output\notification;
use mod_onlyoffice\record\onlyoffice_document;
use moodle_exception;
use moodle_url;
use single_button;

defined('MOODLE_INTERNAL') || die();

class view {
    /** @var cm_info $cm Course module */
    private $cm;

    /**
     * view constructor.
     * @param cm_info $cm Course module
     */
    public function __construct($cm) {
        $this->cm = $cm;
    }

    /**
     * Get the group ID
     * @return int Group ID
     * @throws coding_exception
     */
    public function get_group_id(): int {
        global $PAGE, $USER;

        $groupid = groups_get_activity_group($this->cm, true);

        if ($groupid === false) {
            return 0; // No group, nothing left to do.
        }

        if ($groupid !== 0) {
            return $groupid; // Group ID already set so we'll use that.
        }

        // Start with groups we are a member of.
        $allgroups = has_capability('moodle/site:accessallgroups', $PAGE->context);
        $allowedgroups = groups_get_all_groups($this->cm->course, $USER->id, $this->cm->groupingid);

        // Not a member of any groups, but can see some groups, so get the full list.
        if (!$allowedgroups && ($allgroups || groups_get_activity_groupmode($this->cm) === VISIBLEGROUPS)) {
            $allowedgroups = groups_get_all_groups($this->cm->course, 0, $this->cm->groupingid);
        }

        if (!$allowedgroups) {
            return 0; // No access to any groups.
        }

        // Found a group, get the first one.
        $firstgroup = reset($allowedgroups);
        return $firstgroup->id;
    }

    /**
     * Get the lock icon HTML
     * @param onlyoffice_document $document OnlyOffice document object
     * @return string HTML for the lock icon
     * @throws moodle_exception
     * @throws coding_exception
     */
    public function get_lock_icon(onlyoffice_document $document): string {
        global $PAGE, $OUTPUT;

        // Build the data for the template.
        $canupdate = $document->can_lock_unlock();
        $islocked = $document->is_locked();

        $url = null;
        $helpicon = null;

        if ($canupdate) {
            $params = ['sesskey' => sesskey(), 'action' => $islocked ? 'unlock' : 'lock'];
            $url = new moodle_url($PAGE->url, $params);
            $helpicon = $OUTPUT->help_icon('lock', 'mod_onlyoffice');
        }

        // Render the template.
        $data = (object)[
            'canupdate' => $document->can_lock_unlock(),
            'islocked' => $islocked,
            'url' => $url,
            'helpicon' => $helpicon,
        ];
        return $OUTPUT->render_from_template('mod_onlyoffice/lockicon', $data);
    }

    /**
     * Handle what to display in offline mode
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function handle_offline_mode(): void {
        global $OUTPUT, $PAGE;

        $cmid = required_param('id', PARAM_INT);
        $groupid = optional_param('group', 0, PARAM_INT);
        $download = optional_param('download', 0, PARAM_INT);
        $confirmed = optional_param('confirm', 0, PARAM_INT);

        // Show offline message and download button when we haven't decided whether to download the file.
        if (!$download && !$confirmed) {
            echo $OUTPUT->notification(get_string('serveroffline', 'mod_onlyoffice'), notification::NOTIFY_INFO);

            // Download button.
            $downloadurl = $PAGE->url;
            $downloadurl->param('download', 1);
            echo $OUTPUT->single_button($downloadurl, get_string('download'), 'get');

            return;
        }

        // Confirm download button.
        $downloadurl = $PAGE->url;
        $downloadurl->params(['download' => 1, 'confirm' => 1]);
        $downloadbutton = new single_button($downloadurl, get_string('download'), 'get');

        // Cancel button.
        $cancelurl = new moodle_url('/mod/onlyoffice/view.php', ['id' => $cmid, 'group' => $groupid]);
        $cancelbutton = new single_button($cancelurl, get_string('goback', 'mod_onlyoffice'), 'get');

        // Confirmation box.
        echo $OUTPUT->confirm(get_string('confirmdownload', 'mod_onlyoffice'), $downloadbutton, $cancelbutton);
    }
}
