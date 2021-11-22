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
 * Global settings
 *
 * @package mod_onlyoffice
 * @author Alex Paphitis <alex@paphitis.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_onlyoffice\onlyoffice;

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig && $ADMIN->fulltree) {

    // Document server URL.
    $settings->add(new admin_setting_configtext(
        'mod_onlyoffice/documentserverurl',
        new lang_string('documentserverurl', 'mod_onlyoffice'),
        new lang_string('documentserverurldesc', 'mod_onlyoffice'),
        '',
        PARAM_URL
    ));

    // Document server secret.
    $settings->add(new admin_setting_configtext(
        'mod_onlyoffice/documentserversecret',
        new lang_string('documentserversecret', 'mod_onlyoffice'),
        new lang_string('documentserversecretdesc', 'mod_onlyoffice'),
        ''
    ));

    // Default format.
    $settings->add(new admin_setting_configselect(
        'mod_onlyoffice/defaultformat',
        new lang_string('defaultformat', 'mod_onlyoffice'),
        '',
        onlyoffice::FORMAT_UPLOAD,
        onlyoffice::get_format_menu()
    ));

    // Default display.
    $settings->add(new admin_setting_configselect(
        'mod_onlyoffice/defaultdisplay',
        new lang_string('defaultdisplay', 'mod_onlyoffice'),
        '',
        onlyoffice::DISPLAY_CURRENT,
        onlyoffice::get_display_menu()
    ));

    // Yes/no options.
    $yesno = [1 => new lang_string('yes'), 0 => new lang_string('no')];

    // Whether or not to use default display name.
    $settings->add(new admin_setting_configselect(
        'mod_onlyoffice/defaultdisplayname',
        new lang_string('defaultdisplayname', 'mod_onlyoffice'),
        '',
        1,
        $yesno
    ));

    // Default display description.
    $settings->add(new admin_setting_configselect(
        'mod_onlyoffice/defaultdisplaydescription',
        new lang_string('defaultdisplaydescription', 'mod_onlyoffice'),
        '',
        1,
        $yesno
    ));

    // Override default template file for spreadsheets.
    $settings->add(new admin_setting_configstoredfile(
        'mod_onlyoffice/overridetemplatespreadsheet',
        new lang_string('overridetemplatespreadsheet', 'mod_onlyoffice'),
        '',
        'templates',
        onlyoffice::FORMAT_SPREADSHEET_ITEM_ID,
        ['accepted_types' => onlyoffice::get_accepted_types_spreadsheets()]
    ));

    // Override default template file for presentations.
    $settings->add(new admin_setting_configstoredfile(
        'mod_onlyoffice/overridetemplatepresentation',
        new lang_string('overridetemplatepresentation', 'mod_onlyoffice'),
        '',
        'templates',
        onlyoffice::FORMAT_PRESENTATION_ITEM_ID,
        ['accepted_types' => onlyoffice::get_accepted_types_presentations()]
    ));

    // Override default template file for word documents.
    $settings->add(new admin_setting_configstoredfile(
        'mod_onlyoffice/overridetemplateworddocument',
        new lang_string('overridetemplateworddocument', 'mod_onlyoffice'),
        '',
        'templates',
        onlyoffice::FORMAT_WORDPROCESSOR_ITEM_ID,
        ['accepted_types' => onlyoffice::get_accepted_types_word_documents()]
    ));

    // Default initial text.
    $settings->add(new admin_setting_configtextarea(
        'mod_onlyoffice/defaultinitialtext',
        new lang_string('defaultinitialtext', 'mod_onlyoffice'),
        '',
        ''
    ));

    // Default for whether the document can be downloaded.
    $settings->add(new admin_setting_configselect(
        'mod_onlyoffice/defaultcandownload',
        new lang_string('defaultcandownload', 'mod_onlyoffice'),
        '',
        1,
        $yesno
    ));

    // Default for whether the document can be printed.
    $settings->add(new admin_setting_configselect(
        'mod_onlyoffice/defaultcanprint',
        new lang_string('defaultcanprint', 'mod_onlyoffice'),
        '',
        1,
        $yesno
    ));
}
