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
 * Strings for component 'mod_apidocs', language 'es'
 *
 * @package     mod_apidocs
 * @author      Felix Manrique / Mr Jacket
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['modulename'] = 'Documentación API';
$string['modulenameplural'] = 'Documentaciones API';
$string['modulename_help'] = 'El módulo de Documentación API permite al profesor mostrar especificaciones técnicas directamente dentro de la interfaz del curso, sin herramientas externas.

Inspirado en el visor nativo de GitLab, este plugin detecta y renderiza automáticamente:

* **AsyncAPI (v2 y v3):** Para arquitecturas orientadas a eventos (Kafka, RabbitMQ, MQTT).
* **OpenAPI (Swagger):** Para APIs RESTful estándar.
* **Markdown:** Para documentación técnica general.

**Características principales:**
* Funciona **totalmente offline/local** (no depende de CDNs externos).
* Interfaz limpia de lectura (oculta bloques innecesarios).
* Ideal para campus de ingeniería y entornos corporativos restringidos.';

$string['pluginname'] = 'Documentación API';
$string['pluginadministration'] = 'Administración Documentación API';
$string['specfiles'] = 'Archivos de especificación';
$string['specfiles_help'] = 'Sube aquí tu archivo de especificación OpenAPI, AsyncAPI (.yaml, .yml, .json) o documentación Markdown (.md).';
$string['name'] = 'Nombre';
$string['requiredspecfile'] = 'Debes subir un archivo de especificación (.yaml/.yml/.json/.md)';
$string['privacy:metadata'] = 'El plugin API Docs almacena archivos subidos por usuarios.';
$string['apidocs:addinstance'] = 'Agregar nueva instancia de Documentación API';
$string['apidocs:view'] = 'Ver contenido de Documentación API';
