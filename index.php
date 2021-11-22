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
 * View all OnlyOffice documents activities
 *
 * @package mod_onlyoffice
 * @author Alex Paphitis <alex@paphitis.net> based on code from Olumuyiwa Taiwo <muyi.taiwo@logicexpertise.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_onlyoffice\event\course_module_instance_list_viewed;

require(__DIR__.'/../../config.php');
global $CFG, $PAGE, $DB, $OUTPUT;

$id = required_param('id', PARAM_INT); // Course ID.
$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);

$PAGE->set_url('/mod/onlyoffice/index.php', ['id' => $id]);
$PAGE->set_pagelayout('incourse');

require_course_login($course);

// Instance list has been viewed event.
course_module_instance_list_viewed::trigger_from_course($course);

// Language strings - course module specific.
$strmodule = get_string('modulename', 'mod_onlyoffice');
$strmodules = get_string('modulenameplural', 'mod_onlyoffice');

// Language strings - general.
$strsectionname = get_string('sectionname', "format_$course->format");
$strname = get_string('name');
$strintro = get_string('moduleintro');
$strlastmodified = get_string('lastmodified');

// Page setup.
$PAGE->set_title("$course->shortname: $strmodules");
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strmodules);

echo $OUTPUT->header();
echo $OUTPUT->heading($strmodules);

// Try get all the instances of the activity module if there are any.
if (!$instances = get_all_instances_in_course('onlyoffice', $course)) {
    $url = new moodle_url('/course/view.php', ['id' => $course->id]);
    $message = get_string('thereareno', 'moodle', $strmodules);
    notice($message, $url);
    echo $OUTPUT->footer();
    exit;
}

// Build our HTML table.
$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

// Whether or not the course format uses sections.
$usesections = course_format_uses_sections($course->format);

// Table columns and alignment depend on whether or not the course format uses sections.
$table->head = $usesections ? [$strsectionname, $strname, $strintro] : [$strlastmodified, $strname, $strintro];
$table->align = $usesections ? ['center', 'left', 'left'] : ['left', 'left', 'left'];

$modinfo = get_fast_modinfo($course);
$currentsection = '';

foreach ($instances as $instance) {
    $cm = $modinfo->cms[$instance->coursemodule];

    // Build each of the columns that make up our table row.

    // Name content.
    $icon = !empty($cm->icon) ? $OUTPUT->pix_icon($cm->icon, get_string('modulename', $cm->modname)) : '';
    $content = $icon . format_string($instance->name);

    // Name attributes.
    $classes = $instance->visible ? '' : 'dimmed'; // Hidden modules are dimmed.
    $extra = $cm->extra ?? []; // Extra attributes.
    $attributes = array_merge(['class' => $classes, 'onclick' => $cm->onclick, $extra]);

    $name = html_writer::link($cm->url, $content, $attributes);

    // Module info.
    $moduleintro = format_module_intro('onlyoffice', $instance, $cm->id);

    // We're not using sections.
    if (!$usesections) {
        $topic = html_writer::span(userdate($instance->timemodified), 'smallinfo');
        $table->data[] = [$topic, $name, $moduleintro];
        continue;
    }

    // We're using sections.
    if ($instance->section === $currentsection) {
        $topic = '';
        $table->data[] = [$topic, $name, $moduleintro];
        continue;
    }

    $currentsection = $instance->section;

    // Add the row to the table.
    $topic = $instance->section ? get_section_name($course, $instance->section) : '';
    $table->data[] = [$topic, $name, $moduleintro];
}

echo html_writer::table($table);

echo $OUTPUT->footer();
