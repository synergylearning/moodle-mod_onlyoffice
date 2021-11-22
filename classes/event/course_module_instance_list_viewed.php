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
 * Instance list viewed event
 *
 * @package mod_onlyoffice
 * @copyright 2019 Davo Smith, 2020 Alex Paphitis <alex@paphitis.net>, Synergy Learning
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_onlyoffice\event;

use coding_exception;
use context_course;
use stdClass;

defined('MOODLE_INTERNAL') || die();

class course_module_instance_list_viewed extends \core\event\course_module_instance_list_viewed {
    /**
     * Trigger the event from a course object
     * @param stdClass $course Course record to trigger the event
     * @throws coding_exception
     */
    public static function trigger_from_course(stdClass $course): void {
        $params = ['context' => context_course::instance($course->id)];
        $event = self::create($params);
        $event->add_record_snapshot('course', $course);
        $event->trigger();
    }
}
