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
 * Version - so web services are recognised
 *
 * @package    local
 * @subpackage gadgets
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

////////////////////////////////////////////////////////////////////////////////
//  Code fragment to define the version
//  This fragment is called by moodle_needs_upgrading() and /admin/index.php
////////////////////////////////////////////////////////////////////////////////

$plugin->version  = 2011112800;   // The (date) version of this module
$plugin->requires = 2010080300;   // Requires this Moodle version
