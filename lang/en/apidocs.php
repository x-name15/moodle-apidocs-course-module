<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Strings for component 'mod_apidocs', language 'en'
 *
 * @package     mod_apidocs
 * @author      Felix Manrique / Mr Jacket
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


$string['modulename'] = 'API Docs';
$string['modulenameplural'] = 'API Docs';
$string['modulename_help'] = ' The API Documentation module enables a teacher to display technical specifications directly within the course interface without external tools.

It automatically detects and renders the following formats:
* **AsyncAPI (v2 & v3):** For Event-Driven Architectures (Kafka, MQTT, etc.).
* **OpenAPI (Swagger):** For RESTful APIs.
* **Markdown:** For general documentation.

**Key Features:**
* Fully **offline/local** (no external CDN dependencies).
* Clean reading interface (hides unnecessary blocks).
* Ideal for engineering campuses and restricted corporate environments.';

$string['pluginname'] = 'API Docs';
$string['pluginadministration'] = 'API Docs administration';
$string['specfiles'] = 'Spec files';
$string['specfiles_help'] = 'Upload your OpenAPI, AsyncAPI spec file (.yaml, .yml, .json) or Markdown documentation (.md).';
$string['name'] = 'Name';
$string['requiredspecfile'] = 'You must upload a spec file (.yaml/.yml/.json)';
$string['privacy:metadata'] = 'The API Docs plugin stores files uploaded by users.';
$string['apidocs:addinstance'] = 'Add a new API Docs instance';
$string['apidocs:view'] = 'View API Docs content';
$string['btn_preview'] = 'Preview';
$string['btn_raw'] = 'Raw Code';
$string['btn_copy'] = 'Copy';
$string['msg_copied'] = 'Copied!';
$string['search_placeholder'] = 'Search...'; 