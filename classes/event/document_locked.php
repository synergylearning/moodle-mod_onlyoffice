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
 * Document locked event
 *
 * @package mod_onlyoffice
 * @copyright 2019 Davo Smith, 2020 Alex Paphitis <alex@paphitis.net>, Synergy Learning
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_onlyoffice\event;

use coding_exception;
use context_module;
use core\event\base;
use moodle_exception;
use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

class document_locked extends base {
    /**
     * Trigger event using document
     * @param int $cmid Course module ID
     * @param stdClass $documentrecord Document database record
     * @throws coding_exception
     */
    public static function trigger_from_document(int $cmid, stdClass $documentrecord) {
        $params = [
            'context' => context_module::instance($cmid),
            'objectid' => $documentrecord->id,
            'other' => [
                'groupid' => $documentrecord->groupid,
                'documentkey' => $documentrecord->documentkey,
            ],
        ];
        $event = self::create($params);
        $event->add_record_snapshot('onlyoffice_document', $documentrecord);
        $event->trigger();
    }

    /**
     * Init method.
     */
    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'onlyoffice_document';
    }

    /**
     * Returns description of what happened.
     * @return string Description of the event
     * @throws coding_exception
     */
    public function get_description(): string {
        $a = (object)[
            'userid' => $this->userid,
            'objectid' => $this->objectid,
            'groupid' => $this->other['groupid'],
            'contextinstanceid' => $this->contextinstanceid,
        ];
        return get_string('eventdocumentlockeddesc', 'mod_onlyoffice', $a);
    }

    /**
     * Return localised event name.
     * @return string Localised name of the event
     * @throws coding_exception
     */
    public static function get_name(): string {
        return get_string('eventdocumentlocked', 'mod_onlyoffice');
    }

    /**
     * Get URL related to the action
     * @return moodle_url URL to give further context of this event
     * @throws moodle_exception
     */
    public function get_url(): moodle_url {
        $params = ['id' => $this->contextinstanceid, 'group' => $this->other['groupid']];
        return new moodle_url('/mod/onlyoffice/view.php', $params);
    }

    /**
     * Custom data validation
     * @throws coding_exception
     * @return void
     */
    protected function validate_data(): void {
        parent::validate_data();

        // Must include group ID.
        if (!isset($this->other['groupid'])) {
            throw new coding_exception('The \'groupid\' value must be set in other.');
        }

        // Must include document key.
        if (!isset($this->other['documentkey'])) {
            throw new coding_exception('The \'documentkey\' value must be set in other.');
        }

        // The context leve must match.
        if ($this->contextlevel != CONTEXT_MODULE) {
            throw new coding_exception('Context level must be CONTEXT_MODULE.');
        }
    }

    /**
     * Used for mapping events on restoring
     * @return array Mapping for restoring
     */
    public static function get_objectid_mapping(): array {
        return ['db' => 'onlyoffice_document', 'restore' => 'onlyoffice_document'];
    }

    /**
     * Other mappings
     * @return array Other mapping information
     */
    public static function get_other_mapping(): array {
        $othermapped = [];

        $othermapped['documentkey'] = ['db' => 'onlyoffice', 'restore' => 'onlyoffice'];
        $othermapped['groupid'] = ['db' => 'group', 'restore' => 'group'];

        return $othermapped;
    }
}
