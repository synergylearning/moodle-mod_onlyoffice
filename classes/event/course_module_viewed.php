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
 * Course module viewed event
 *
 * @package mod_onlyoffice
 * @copyright 2019 Davo Smith, 2020 Alex Paphitis <alex@paphitis.net>, Synergy Learning
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_onlyoffice\event;

use cm_info;
use coding_exception;
use context_module;
use stdClass;

defined('MOODLE_INTERNAL') || die();

class course_module_viewed extends \core\event\course_module_viewed {
    /**
     * Trigger the event using a course module
     * @param stdClass $course Course record
     * @param cm_info $cm Course module record
     * @param stdClass $activityinstancerecord Database record for activity instance
     * @throws coding_exception
     */
    public static function trigger_from_course_cm(stdClass $course, cm_info $cm, stdClass $activityinstancerecord) {
        $params = [
            'context' => context_module::instance($cm->id),
            'objectid' => $activityinstancerecord->id,
        ];
        $event = self::create($params);

        $event->add_record_snapshot('course_modules', $cm);
        $event->add_record_snapshot('course', $course);
        $event->add_record_snapshot('onlyoffice', $activityinstancerecord);

        $event->trigger();
    }

    /**
     * Init method.
     */
    protected function init(): void {
        $this->data['objecttable'] = 'onlyoffice';
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Used for mapping events on restoring
     * @return array Mapping for restoring
     */
    public static function get_objectid_mapping(): array {
        return ['db' => 'onlyoffice', 'restore' => 'onlyoffice'];
    }
}
