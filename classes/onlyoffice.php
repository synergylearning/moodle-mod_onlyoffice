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
 * OnlyOffice
 *
 * @package mod_onlyoffice
 * @author Alex Paphitis <alex@paphitis.net> based on code from Olumuyiwa Taiwo <muyi.taiwo@logicexpertise.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_onlyoffice;

use coding_exception;
use dml_exception;

defined('MOODLE_INTERNAL') || die();

class onlyoffice {
    /** @var string File format - File uploaded by user */
    const FORMAT_UPLOAD = 'upload';

    /** @var string File format - Text document created from initial text */
    const FORMAT_TEXT = 'text';

    /** @var string File format - Spreadsheet template */
    const FORMAT_SPREADSHEET = 'spreadsheet';

    /** @var string File format - Word processor template */
    const FORMAT_WORDPROCESSOR = 'wordprocessor';

    /** @var string File format - Presentation template */
    const FORMAT_PRESENTATION = 'presentation';

    /** @var int Item ID of the global template file - Spreadsheet */
    const FORMAT_SPREADSHEET_ITEM_ID = 1;

    /** @var int Item ID of the global template file - Word processor */
    const FORMAT_WORDPROCESSOR_ITEM_ID = 2;

    /** @var int Item ID of the global template file - Presentation */
    const FORMAT_PRESENTATION_ITEM_ID = 3;

    /** @var string Display mode - Current window */
    const DISPLAY_CURRENT = 'current';

    /** @var string Display mode - Open in a new tab */
    const DISPLAY_NEW = 'new';

    /** @var string Type of file area the file is stored in - Initial */
    const FILEAREA_INITIAL = 'initial';

    /** @var string Type of file area the file is stored in - Group scope */
    const FILEAREA_GROUP = 'group';

    /** @var string Type of file area the file is stored in - Global templates */
    const FILEAREA_TEMPLATES = 'templates';

    /** @var int Server timeout time in seconds */
    const SERVER_CONNECT_TIMEOUT = 5;

    /** @var string[] Valid file areas */
    const FILEAREAS = [
        self::FILEAREA_INITIAL,
        self::FILEAREA_GROUP,
        self::FILEAREA_TEMPLATES,
    ];

    /** @var int Maximum length of an activity name */
    const ACTIVITY_NAME_LENGTH_MAX = 64;

    /** @var bool Default state for a document */
    const LOCKED_DEFAULT = false;

    /**
     * Get the format menu options
     * @return array All the formats supported
     * @throws coding_exception
     */
    public static function get_format_menu(): array {
        return [
            self::FORMAT_UPLOAD => get_string(self::FORMAT_UPLOAD, 'mod_onlyoffice'),
            self::FORMAT_TEXT => get_string(self::FORMAT_TEXT, 'mod_onlyoffice'),
            self::FORMAT_SPREADSHEET => get_string(self::FORMAT_SPREADSHEET, 'mod_onlyoffice'),
            self::FORMAT_WORDPROCESSOR => get_string(self::FORMAT_WORDPROCESSOR, 'mod_onlyoffice'),
            self::FORMAT_PRESENTATION => get_string(self::FORMAT_PRESENTATION, 'mod_onlyoffice'),
        ];
    }

    /**
     * Get the display menu options
     * @return array Supported display options
     * @throws coding_exception
     */
    public static function get_display_menu(): array {
        return [
            self::DISPLAY_CURRENT => get_string(self::DISPLAY_CURRENT, 'mod_onlyoffice'),
            self::DISPLAY_NEW => get_string(self::DISPLAY_NEW, 'mod_onlyoffice'),
        ];
    }

    /**
     * Get all the accepted types
     * @return string[] Accepted file types
     */
    public static function get_accepted_types(): array {
        $alltypes = array_merge(
            self::get_accepted_types_spreadsheets(),
            self::get_accepted_types_presentations(),
            self::get_accepted_types_word_documents(),
            self::get_accepted_types_drawings()
        );

        return array_unique($alltypes);
    }

    /**
     * Get accepted types for spreadsheets
     * @return string[] Accepted types of spreadsheet files
     */
    public static function get_accepted_types_spreadsheets(): array {
        return [
            '.xls',
            '.xlsx',
            '.ots',
            '.ods',
        ];
    }

    /**
     * Get accepted types for presentations
     * @return string[] Accepted types of presentation files
     */
    public static function get_accepted_types_presentations(): array {
        return [
            '.ppt',
            '.pptx',
            '.otp',
            '.odp',
        ];
    }

    /**
     * Get accepted types for word documents
     * @return string[] Accepted types of word documents
     */
    public static function get_accepted_types_word_documents(): array {
        return [
            '.txt', '.rtf', // Text.
            '.html', '.htm', // HTML.
            '.doc', '.docx', '.odt', // Word documents.
        ];
    }

    /**
     * Get accepted types for drawings
     * @return string[] Accepted types of drawings
     */
    public static function get_accepted_types_drawings(): array {
        return [
            '.odg', // Drawing.
        ];
    }

    /**
     * Get the default value for whether the document can be downloaded
     * @return bool Default option selected for whether the document can be download
     * @throws dml_exception
     */
    public static function get_default_can_download(): bool {
        return (bool) get_config('mod_onlyoffice', 'defaultcandownload');
    }

    /**
     * Get the default value for whether the document can be printed
     * @return bool Default option selected for whether the document can be printed
     * @throws dml_exception
     */
    public static function get_default_can_print(): bool {
        return (bool) get_config('mod_onlyoffice', 'defaultcanprint');
    }

    /**
     * Get the default initial text
     * @return string Default initial text
     * @throws dml_exception
     */
    public static function get_default_initial_text(): string {
        return get_config('mod_onlyoffice', 'defaultinitialtext');
    }

    /**
     * Get the document server URL
     * @return string URL of the OnlyOffice document server
     * @throws dml_exception
     */
    public static function get_server_url(): string {
        return get_config('mod_onlyoffice', 'documentserverurl');
    }

    /**
     * Get the document server secret key
     * @return string Secret key for accessing the document server
     * @throws dml_exception
     */
    public static function get_secret_key(): string {
        return get_config('mod_onlyoffice', 'documentserversecret');
    }

    /**
     * Is the OnlyOffice document server online?
     * @return bool Whether or not the OnlyOffice document server is online
     * @throws dml_exception
     */
    public static function is_server_online(): bool {
        $documentserverurl = self::get_server_url();

        // Try connect to the document server.
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $documentserverurl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::SERVER_CONNECT_TIMEOUT);

        curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode != 200 && $httpcode != 302) {
            return false; // Not an OK status code.
        }

        return true;
    }
}