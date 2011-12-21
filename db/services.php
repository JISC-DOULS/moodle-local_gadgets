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
 * gadget external functions and service definitions.
 *
 * @package    local
 * @subpackage gadgets
 * @copyright  2011 The Open university
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = array(
    'local_gadgets_get_collabtoolsupdate' => array(
        'classname'   => 'local_collabtools',
        'methodname'  => 'get_update',
        'classpath'   => 'local/gadgets/collabtools/externallib.php',
        'description' => 'Returns info on collaborative tools.',
        'type'        => 'read',
        'capabilities'=> null,
    ),
);
