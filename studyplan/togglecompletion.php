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
 * Layer for standard course/togglecompletion.php so it can be called by gadget
 *
 * @package    local
 * @subpackage gadgets/studyplan
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//Allow for cross domain XMLHttpRequests
$headers = getallheaders();
if (isset($headers['Origin'])) {
    header("Access-Control-Allow-Origin: ".$headers['Origin']);//need to be specific * won't work
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header('Access-Control-Allow-Headers: X-Requested-With, Content-Type');
    header('Access-Control-Allow-Credentials: true');
    // Set the age to 20 day to improve speed/caching.
    header('Access-Control-Max-Age: 1728000');
    header('Cache-Control: no-cache');
    header('Pragma: no-cache');

}


// Exit early so the page isn't fully loaded for options requests
if (strtolower($_SERVER['REQUEST_METHOD']) == 'options') {
    exit();
}

require_once('../../../config.php');
global $SESSION;

// Check parameters
$cmid = required_param('id', PARAM_INT);
$targetstate = required_param('completionstate', PARAM_INT);

//Add in required post params
$_POST['sesskey'] = sesskey();

if (!isset($_REQUEST['fromajax'])) {
    $_POST['fromajax'] = 1;
}

//Call original completion code
global $CFG;
chdir($CFG->dirroot .'/course/');//set working dir to here as page uses relative includes
require($CFG->dirroot .'/course/togglecompletion.php');
