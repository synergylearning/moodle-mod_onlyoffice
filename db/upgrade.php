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
 * Upgrade steps
 *
 * @package   mod_onlyoffice
 * @copyright 2021 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_onlyoffice_upgrade($oldversion = 0) {
    global $DB;

    if ($oldversion < 2021061600) {
        // Fix any duplicate document keys.
        $sql = "
            SELECT d.id
              FROM {onlyoffice_document} d
              JOIN {onlyoffice_document} d2 ON d2.id < d.id AND d2.documentkey = d.documentkey
        ";
        foreach ($DB->get_recordset_sql($sql) as $dupkey) {
            $documentkey = \mod_onlyoffice\record\onlyoffice_document::generate_document_key();
            $DB->set_field('onlyoffice_document', 'documentkey', $documentkey, ['id' => $dupkey->id]);
        }
        upgrade_mod_savepoint(true, 2021061600, 'onlyoffice');
    }

    return true;
}
