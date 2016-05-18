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
 * Event handler implementatins for wims module
 *
 * @copyright  2015 Edunao SAS (contact@edunao.com)
 * @author     Sadge (daniel@edunao.com)
 * @package    mod_wims
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * course_module_updated event handler
 *
 * @param \core\event\course_module_updated $event
 */
function on_course_module_updated(\core\event\course_module_updated $event) {
    // we're only interested in our own updates
    if ( $event->other['modulename'] != 'wims' ){
        return;
    }

    // ignore the event if we've come from the mod_form as the name will already have been sent to WIMS
    global $WIMS_MOD_FORM;
    if ($WIMS_MOD_FORM){
        return;
    }

    // setup a fake course module to provide the data that the wims interface needs
    $cm=new StdClass;
    $cm->id=$event->objectid;

    // try to send the updated name to WIMS
    require_once(dirname(__FILE__).'/wimsinterface.class.php');
    $wimsdata=array( "description" => $event->other['name'] );
    $config=get_config('wims');
    $wims=new wims_interface($config,$config->debugsettings);
    $wims->updateclassconfigformodule($cm,$wimsdata);
}
