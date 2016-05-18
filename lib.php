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
 * Moodle interface library for wims
 *
 * @copyright  2015 Edunao SAS (contact@edunao.com)
 * @author     Sadge (daniel@edunao.com)
 * @package    mod_wims
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
// this is lib.php - add code here for interfacing this module to Moodle internals

defined('MOODLE_INTERNAL') || die;

/**
 * List of features supported in wims module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function wims_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_GROUPMEMBERSONLY:        return false;
        case FEATURE_MOD_INTRO:               return false;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return false;
        case FEATURE_GRADE_HAS_GRADE:         return true;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return false;
        case FEATURE_SHOW_DESCRIPTION:        return false;

        default: return null;
    }
}

/**
 * Returns all other caps used in module
 * @return array
 */
function wims_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function wims_reset_userdata($data) {
    return array();
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function wims_get_view_actions() {
    return array('view', 'view all');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function wims_get_post_actions() {
    return array('update', 'add');
}

/**
 * Add wims instance.
 * @param object $data
 * @param object $mform
 * @return int new url instance id
 */
function wims_add_instance($data, $mform) {
    global $CFG, $DB;
    $data->id = $DB->insert_record('wims', $data);

    return $data->id;
}

/**
 * Update wims instance.
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function wims_update_instance($data, $mform) {
    global $CFG, $DB;

    $parameters = array();
    for ($i=0; $i < 100; $i++) {
        $parameter = "parameter_$i";
        $variable  = "variable_$i";
        if (empty($data->$parameter) or empty($data->$variable)) {
            continue;
        }
        $parameters[$data->$parameter] = $data->$variable;
    }
    $data->parameters = serialize($parameters);

    $data->timemodified = time();
    $data->id           = $data->instance;

    $DB->update_record('wims', $data);

    return true;
}

/**
 * Delete wims instance.
 * @param int $id
 * @return bool true
 */
function wims_delete_instance($id) {
    global $DB;

    if (!$instance = $DB->get_record('wims', array('id'=>$id))) {
        return false;
    }

    // note: all context files are deleted automatically

    $DB->delete_records('wims', array('id'=>$url->id));

    return true;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 *
 * See {@link get_array_of_activities()} in course/lib.php
 *
 * @param object $coursemodule
 * @return cached_cm_info info
 */
function wims_get_coursemodule_info($coursemodule) {
    global $CFG, $DB;

    if (!$instance = $DB->get_record('wims', array('id'=>$coursemodule->instance),
            'name')) {
        return NULL;
    }

    $info = new cached_cm_info();
    $info->name = $instance->name;
    $info->icon = null;

    // display as a new window
    $fullurl = "$CFG->wwwroot/mod/wims/view.php?id=$coursemodule->id&amp;redirect=1";
    $info->onclick = "window.open('$fullurl'); return false;";

    return $info;
}
/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function wims_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $module_pagetype = array('mod-wims-*'=>get_string('page-mod-wims-x', 'wims'));
    return $module_pagetype;
}

/**
 * Export URL resource contents
 *
 * @return array of file content
 */
function wims_export_contents($cm, $baseurl) {
    $contents = array();
    return $contents;
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 * @return boolean true on success
 */
function wims_cron($forceupdate=null){
    global $CFG, $DB;
    
    require_once($CFG->libdir.'/gradelib.php');

    // if the cron has never been run before then prime the system with a dummy 'last run' date
    if (!isset($CFG->wims_updatetimelast)) {
        set_config('wims_updatetimelast', 0);
    }

    // calculate the time corresponding to 1:30am 'today'
    // NOTE: We're using 1:30am as MOODLE appears to do a lot of stuff at midnight and it's good to spread the load.
    $lastupdatetime=$CFG->wims_updatetimelast;
    $sitetimezone = $CFG->timezone;
    $nextupdatetime = usergetmidnight($lastupdatetime, $sitetimezone) + ( 24 + 1 ) * 60 * 60 + 30 * 60;

    // if we already ran an update since '1:30am today' then nothing to do so drop out
    $timenow = time();
    if ($timenow < $nextupdatetime && !$forceupdate) {
        return true;
    }

    // we're starting a new run so upgrade the 'last update' timestamp
    // Note that we do this first in order to minimise risk of race conditions if the run takes time
    set_config('wims_updatetimelast', $timenow);

    // log a message and load up key utility classes
    mtrace('Synchronising WIMS activity scores to grade book');
    require_once("wimsinterface.class.php");
    $config=get_config('wims');
    $wims=new wims_interface($config,$config->debugcron);

    // iterate over the set of WIMS activities in the system
    $moduleinfo = $DB->get_record('modules', array('name' => 'wims'));
    $coursemodules = $DB->get_records('course_modules', array('module' => $moduleinfo->id), 'id', 'id,course,instance,section');
    foreach($coursemodules as $cm){
        mtrace('- PROCESSING: course='.$cm->course.' section='.$cm->section.' cm='.$cm->id.' instance='.$cm->instance );
        
        // make sure the course is correctly accessible
        $isaccessible=$wims->verifyclassaccessible($cm);
        if (!$isaccessible){
            mtrace('  - ALERT: Ignoring class as it is inaccessible - it may not have been setup yet');
            continue;
        }

        // get the sheet index for this wims course
        $sheetindex=$wims->getsheetindex($cm);
        if ($sheetindex==null){
            mtrace('  ERROR: Failed to fetch sheet index for WIMS course: cm='.$cm->id );
            continue;
        }
        
        // iterate over the contents of the sheet index, storing pertinent entries in the 'required sheets' array
        $requiredsheets=array();
        $sheettitles=array();
        foreach ($sheetindex as $sheettype=>$sheets){
            $requiredsheets[$sheettype]=array();
            $sheettitles[$sheettype]=array();
            foreach ($sheets as $sheetid => $sheetsummary){
                // ignore sheets that are in preparation as WIMS complains if one tries to access their scores
                if ($sheetsummary->state==0){
                    mtrace('  - Ignoring: '.$sheettype.' '.$sheetid.': "'.$title.'" [state='.$sheetsummary->state.'] - due to STATE');
                    continue;
                }
                $title=$sheetsummary->title;
                // if the sheet name is tagged with a '*' then strip it off and process the sheet
                if (substr($title,-1)==='*'){
                    $title=trim(substr($title,0,-1));
                }else{
                    // we don't have a * so if we're not an exam then drop our
                    if ($sheettype!=='exams'){
                        mtrace('  - Ignoring: '.$sheettype.' '.$sheetid.': "'.$title.'" [state='.$sheetsummary->state.'] - due to Lack of *');
                        continue;
                    }
                }
                // we're ready to process the sheet
                mtrace('  * Keeping: '.$sheettype.' '.$sheetid.': "'.$title.'" [state='.$sheetsummary->state.']');
                $requiredsheets[$sheettype][]=$sheetid;
                $sheettitles[$sheettype][$sheetid]=$title;
            }
        }
        
        // fetch the scores for the required sheets
        $sheetscores=$wims->getsheetscores($cm,$requiredsheets);
        if ($sheetscores==null){
            mtrace('  ERROR: Failed to fetch sheet scores for WIMS course: cm='.$cm->id );
            continue;
        }

        // fetch the complete user list from moodle (and hope that we don't run out of RAM)
        $userrecords=$DB->get_records('user',null,'','id,firstname,lastname');
        
        // build a lookup table to get from user names to Moodle user ids
        $userlookup=array();
        foreach($userrecords as $userinfo){
            $wimslogin=$wims->generatewimslogin($userinfo);
            $userlookup[$wimslogin]=$userinfo->id;
        }
        
        // We have an identifier problem: Exams and worksheets are both numbered from 1 up
        // and for scoring we need to have a unique identifier for each scoring column
        // so we're going to use an offset for worksheets
        $itemnumberoffsetforsheettype = array( 'worksheets' => 1000, 'exams' => 0 );

        // iterate over the records to setup meta data - ie to assign sheet names to the correct score columns
        foreach ($sheetscores as $sheettype=>$sheets){
            $itemnumberoffset= $itemnumberoffsetforsheettype[$sheettype];
            foreach ($sheets as $sheetid => $sheetdata){
                // generate the key identifier that allows us to differentiate scores within a single exercise
                $itemnumber = $itemnumberoffset + $sheetid;
                // construct the grade column definition object (with the name of the exercise, score ranges, etc)
                $sheettitle = $sheettitles[$sheettype][$sheetid];
                $params = array( 'itemname' => $sheettitle );
                // The following 2 lines have been commented because they cause the grade update call to fail silently
                //  $params = array( 'grademin' => 0 );
                //  $params = array( 'grademax' => 10 );
                // apply the grade column definition
                $graderesult= grade_update('mod/wims', $cm->course, 'mod', 'wims', $cm->instance, $itemnumber, null, $params);
                if ($graderesult != GRADE_UPDATE_OK){
                    mtrace('  ERROR: Grade update failed to set meta data: '.$sheettype.' '.$sheetid.' @ itemnumber = '.$itemnumber.' => '.$sheettitle);
                }
            }
        }

        // iterate over the sheet scores to write them to the database
        foreach ($sheetscores as $sheettype=>$sheets){
            $itemnumberoffset= $itemnumberoffsetforsheettype[$sheettype];
            foreach ($sheets as $sheetid => $sheetdata){
                // generate the key identifier that allows us to differentiate scores within a single exercise
                $itemnumber = $itemnumberoffset + $sheetid;
                // iterate over the per user records, updating the grade data for each
                foreach ($sheetdata as $username => $scorevalue){
                    if (! array_key_exists($username,$userlookup)){
                        mtrace('  ERROR: Failed to lookup WIMS login in MOODLE users for login: '.$username);
                        continue;
                    }
                    $userid=$userlookup[$username];
                    $grade=array('userid'=>$userid,'rawgrade'=>$scorevalue);
                    $graderesult= grade_update('mod/wims', $cm->course, 'mod', 'wims', $cm->instance, $itemnumber, $grade, null);
                    if ($graderesult != GRADE_UPDATE_OK){
                        mtrace('  ERROR: Grade update failed: '.$sheettype.' '.$sheetid.': '.$userid.' = '.$scorevalue.' @ itemnumber = '.$itemnumber);
                        continue;
                    }
                }
            }
        }
    }
    mtrace('Synchronising WIMS activity scores to grade book => Done.');


    return true;
}

