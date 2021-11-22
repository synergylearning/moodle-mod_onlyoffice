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
 * Language strings
 *
 * @package   mod_onlyoffice
 * @copyright Alex Paphitis <alex@paphitis.net>, 2019 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['candownload'] = 'Can the document be downloaded?';
$string['canprint'] = 'Can the document be printed?';
$string['confirmdownload'] = 'Are you sure you want to download this file?';
$string['current'] = 'Current tab';
$string['defaultcandownload'] = 'Default for whether the document can be downloaded';
$string['defaultcanprint'] = 'Default for whether the document can be printed';
$string['defaultdisplay'] = 'Default display';
$string['defaultdisplaydescription'] = 'Default display description';
$string['defaultdisplayname'] = 'Default display name';
$string['defaultformat'] = 'Default format';
$string['defaultinitialtext'] = 'Default initial text';
$string['display'] = 'Display';
$string['displaydescription'] = 'Display description';
$string['displayname'] = 'Display name';
$string['documentserverurl'] = 'Document editing service address';
$string['documentserverurldesc'] = 'The document editing service address specifies the address of the server with the document services installed. Please replace \'https://documentserver.url\' above with the correct server address';
$string['documentserversecret'] = 'Document server secret';
$string['documentserversecretdesc'] = 'The secret is used to generate the token (an encrypted signature) in the browser for the document editor opening and calling the methods and the requests to the document command service and document conversion service. The token prevents the substitution of important parameters in ONLYOFFICE Document Server requests.';
$string['dnduploaddocument'] = 'Create an OnlyOffice document';
$string['eventdocumentlocked'] = 'OnlyOffice document locked';
$string['eventdocumentlockeddesc'] = 'The user with id {$a->userid} has locked the document with id {$a->objectid}
for group id {$a->groupid} in the document with course module id {$a->contextinstanceid}.';
$string['eventdocumentunlocked'] = 'OnlyOffice document unlocked';
$string['eventdocumentunlockeddesc'] = 'The user with id {$a->userid} has unlocked the document with id {$a->objectid}
for group id {$a->groupid} in the document with course module id {$a->contextinstanceid}.';
$string['format'] = 'Format';
$string['goback'] = 'Go back';
$string['height'] = 'Height (0 for automatic)';
$string['initialfile'] = 'Initial file';
$string['initialtext'] = 'Initial text';
$string['lock'] = 'Lock document';
$string['lock_help'] = 'When a document is locked, it is switched to \'read only\' mode for students (so no further changes can be made). Teachers (or any other user who has permission to \'Lock/unlock an OnlyOffice document\') can continue to make changes to the document.';
$string['locked'] = 'Document locked by teacher';
$string['lockedunlock'] = 'Document currently locked, click here to unlock it and allow editing';
$string['lockoverridden'] = 'You are able to edit this document even when locked';
$string['modulename'] = 'OnlyOffice document';
$string['modulename_help'] = 'The OnlyOffice activity module enables users to collaborate on documents using OnlyOffice,';
$string['modulenameplural'] = 'OnlyOffice documents';
$string['name'] = 'Name';
$string['new'] = 'New tab';
$string['onlyoffice:addinstance'] = 'Add OnlyOffice document to a course';
$string['onlyoffice:editlocked'] = 'Edit a locked OnlyOffice document';
$string['onlyoffice:lock'] = 'Lock/unlock an OnlyOffice document';
$string['onlyoffice:view'] = 'View an OnlyOffice document';
$string['overridetemplatepresentation'] = 'Override default template for presentations';
$string['overridetemplatespreadsheet'] = 'Override default template for spreadsheets';
$string['overridetemplateworddocument'] = 'Override default template for word documents';
$string['permissions'] = 'Document permissions';
$string['pluginadministration'] = 'OnlyOffice document settings';
$string['pluginname'] = 'OnlyOffice document';
$string['presentation'] = 'Presentation';
$string['privacy:metadata'] = 'Documents in this activity are shared by all users in a group, or across the whole course - no information is stored about individual contributions.';
$string['requiredfortext'] = 'Required when the format is \'Specified text\'';
$string['requiredforupload'] = 'Required when the format is \'File upload\'';
$string['returntodocument'] = 'Return to course page';
$string['serveroffline'] = 'Document server is offline, in offline mode you can download the file if we have a copy';
$string['spreadsheet'] = 'Spreadsheet';
$string['text'] = 'Specified text';
$string['unsupportedtype'] = 'Unsupported filetype {$a}';
$string['upload'] = 'File upload';
$string['unlockedlock'] = 'Document currently unlocked, click here to lock it and prevent editing';
$string['width'] = 'Width (0 for automatic)';
$string['wordprocessor'] = 'Wordprocessor document';
