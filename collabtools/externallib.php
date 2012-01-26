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
 * External course format studyplan API
 *
 * @package    format
 * @subpackage studyplan
 * @copyright  2011 The Open university
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
global $CFG;
require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/mod/forumng/mod_forumng.php");
require_once($CFG->dirroot . '/mod/oublog/locallib.php');
require_once($CFG->dirroot . '/mod/ouwiki/locallib.php');

class local_collabtools extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_update_parameters() {
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_INT, 'userid'),
                'courseid' => new external_value(PARAM_INT, 'optional course id', VALUE_OPTIONAL),
            )
        );
    }

    /**
     * Returns content for the collab tools of course(s) for a specific user
     * If you request for a specific course and user is not enrolled will return no data
     * @param INT $userid - user id you want to return their studyplan info
     * @param INT $courseid - Either 0 for all courses enrolled on or specific id
     */
    public static function get_update($userid, $courseid = 0) {
        global $CFG, $DB, $PAGE, $USER, $COURSE, $OUTPUT;

        $retarray = array();

        //validate parameters
        $params = self::validate_parameters(self::get_update_parameters(),
            array('userid' => $userid, 'courseid' => $courseid));

        //Fetch user information
        if (!$user = get_complete_user_data('id', $userid)) {
            //No such user
            throw new moodle_exception('User does not exist');
        }
        $user->auth = 'manual';//Stops errors from auth scripts
        $storeuser = $USER;//try to store 'proper' user global
        $USER = $user;//Set global user to the user we are looking at
        $enrolled = enrol_get_users_courses($userid, true, 'id, visible, shortname, format,
            numsections, modinfo, enablecompletion, theme, startdate', 'startdate ASC');

        //An issue with enrol_get_users_courses is that if users are not unenrolled on the course
        //ending then it still gets returned - check OU course pres table if course has ended
        $vle = false;
        $coursewhere = array();//used to get records
        $enddates = array();//used later to check for enddate
        foreach ($enrolled as $crs) {
            $coursewhere[] = $crs->shortname;
        }
        //check in case there were no courses returned as pointless doing this if so
        if (!empty($coursewhere)) {
            //does the autoload table exist and is there any data
            $sql = 'SELECT table_name
                    FROM information_schema.tables
                    WHERE table_name LIKE \'vl_c_crs_version_pres_a\'
                    AND table_type IN (\'BASE TABLE\', \'LOCAL TEMPORARY\')';
            if ($DB->get_record_sql($sql)) {
                if ($DB->get_record_sql('SELECT * from vl_v_crs_version_pres LIMIT 1')) {
                    $vle = true;
                }
            }

            if ($vle) {
                //get any matching courses from pres list and return finish date
                try {
                    $where = $DB->get_in_or_equal($coursewhere);
                    $sql = 'SELECT id, pres_finish_date, vle_course_short_name from vl_v_crs_version_pres
                    WHERE vle_course_short_name ' . $where[0];
                    $result = $DB->get_records_sql($sql, $where[1]);
                } catch (moodle_exception $e) {
                    $result = array();
                }
                //store in key value array to get later
                foreach ($result as $cos) {
                    $enddates[$cos->vle_course_short_name] =
                    strtotime($cos->pres_finish_date);
                }
            }
        }

        foreach ($enrolled as $course) {
            if ($courseid == 0 || $course->id == $courseid) {
                //We know course has ended
                //TODO - take out hack that makes it work on dev servers...
                if ($vle && !debugging()) {
                    //Course must be a presentation and not finished
                    if (!isset($enddates[$course->shortname]) ||
                        $enddates[$course->shortname] < time()) {
                        continue;
                    }
                }

                if ($course->visible = true) {
                    $retobj = array();
                    $retobj['shortname'] = $course->shortname;
                    $url = new moodle_url('/course/view.php', array('id' => $course->id));
                    $retobj['url'] = $url->out();
                    $retobj['activities'] = array();

                    //Set $PAGE var to course so can get icon - need to set to new object everytime
                    $PAGE = new moodle_page();
                    $PAGE->set_course($course);

                    //TODO Only require module libs when there are any
                    //Get fourms
                    $forums = mod_forumng::get_course_forums($course, $userid);

                    foreach ($forums as $forum) {
                        $cm = $forum->get_course_module();

                        $unread = $forum->get_num_unread_discussions();
                        $forumname = $forum->get_name();
                        $forumid = $forum->get_course_module_id();

                        $activity = array();
                        $activity['name'] = $forumname;
                        $url = new moodle_url('/mod/forumng/view.php', array('id' => $forumid));
                        $activity['url'] = $url->out();
                        if ($unread) {
                            $activity['updates'] = true;
                        } else {
                            $activity['updates'] = false;
                        }
                        //Get icon url
                        $activity['iconurl'] = (string) $OUTPUT->pix_url('icon', 'mod_forumng');

                        $retobj['activities'][] = $activity;
                    }

                    //Get other module types (more tricky as need modinfo)
                    $modinfo = get_fast_modinfo($course, $userid);
                    //OUBLOG
                    if (isset($modinfo->instances['oublog'])) {
                        $blogicon = (string) $OUTPUT->pix_url('icon', 'mod_oublog');
                        foreach ($modinfo->instances['oublog'] as $cm) {
                            if (!$cm->uservisible) {
                                continue;
                            }
                            $activity = array();
                            $activity['name'] = $cm->name;
                            $url = new moodle_url('/mod/oublog/view.php', array('id' => $cm->id));
                            $activity['url'] = $url->out();
                            $activity['updates'] = true;
                            //Check log for last view (seems to be quicker to check each individually)
                            $lastmodified = oublog_get_last_modified($cm, $course, $userid);
                            if (!$lastmodified) {
                                //No content - will always return unseen - so stop this
                                $activity['updates'] = false;
                            } else {
                                $logresult = $DB->record_exists_select('log',
                                    'cmid = ? AND module = \'oublog\' AND time >= ? AND userid = ? AND action = \'view\'',
                                    array($cm->id, $lastmodified, $userid));
                                if ($logresult) {
                                    $activity['updates'] = false;
                                }
                            }

                            //Get icon url
                            $activity['iconurl'] = $blogicon;

                            $retobj['activities'][] = $activity;
                        }
                    }
                    //OUWIKI
                    if (isset($modinfo->instances['ouwiki'])) {
                        $blogicon = (string) $OUTPUT->pix_url('icon', 'mod_ouwiki');
                        foreach ($modinfo->instances['ouwiki'] as $cm) {
                            if (!$cm->uservisible) {
                                continue;
                            }
                            $activity = array();
                            $activity['name'] = $cm->name;
                            $url = new moodle_url('/mod/ouwiki/view.php', array('id' => $cm->id));
                            $activity['url'] = $url->out();
                            $activity['updates'] = true;
                            //Check log for last view (seems to be quicker to check each individually)
                            $lastmodified = ouwiki_get_last_modified($cm, $course, $userid);
                            if (!$lastmodified) {
                                //No content - will always return unseen - so stop this
                                $activity['updates'] = false;
                            } else {
                                $logresult = $DB->record_exists_select('log',
                                    'cmid = ? AND module = \'ouwiki\' AND time >= ? AND userid = ? AND action = \'view\'',
                                    array($cm->id, $lastmodified, $userid));
                                if ($logresult) {
                                    $activity['updates'] = false;
                                }
                            }

                            //Get icon url
                            $activity['iconurl'] = $blogicon;

                            $retobj['activities'][] = $activity;
                        }
                    }
                    //TODO Sort activities array?
                    $retarray[] = $retobj;
                }
            }
        }
        $USER = $storeuser;//Not sure we need to do this, or if it works - but might as well!
        return $retarray;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_update_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'shortname' => new external_value(PARAM_TEXT, 'course short name'),
                    'url' => new external_value(PARAM_TEXT, 'address'),
                    new external_multiple_structure(
                        new external_single_structure(
                            array(
                                'name' => new external_value(PARAM_TEXT, 'activity name'),
                                'url' => new external_value(PARAM_TEXT, 'address'),
                                'updates' => new external_value(PARAM_BOOL, 'unseen content'),
                                'iconurl' => new external_value(PARAM_TEXT, 'icon url'),
                            ), 'activity'
                        )
                    ), 'activities'
                ), 'course'
            )
        );
    }

}
