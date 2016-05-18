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
 * utilities lib for defining wims module admin settings and defaults
 *
 * @copyright  2015 Edunao SAS (contact@edunao.com)
 * @author     Sadge (daniel@edunao.com)
 * @package    mod_wims
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// this is settings.php - add code here to handle administration options for the module

defined('MOODLE_INTERNAL') || die;

define('ADMIN_SETTING_TYPE_TEXT', 'ADMIN_SETTING_TYPE_TEXT');
define('ADMIN_SETTING_TYPE_SELECT', 'ADMIN_SETTING_TYPE_SELECT');
define('ADMIN_SETTING_TYPE_CHECKBOX', 'ADMIN_SETTING_TYPE_CHECKBOX');

function addwimsadminheading($settings,$name){
    $settings->add(new admin_setting_heading(
        "wims/".$name,
        get_string($name, "wims"),
        ""
    ));
}

function addwimsadminsetting($settings,$name,$defaultvalue,$settingtype=ADMIN_SETTING_TYPE_TEXT,$data=null){
    $uniquename  = "wims/".$name;
    $displayname = get_string("adminname".$name, "wims");
    $displayinfo = /*get_string("admindesc".$name, "wims")*/ "";
    switch ($settingtype){

        case ADMIN_SETTING_TYPE_CHECKBOX:
            $settings->add(new admin_setting_configcheckbox(
                $uniquename, $displayname, $displayinfo, $defaultvalue
            ));
            break;

        case ADMIN_SETTING_TYPE_SELECT:
            $settings->add(new admin_setting_configselect(
                $uniquename, $displayname, $displayinfo, $defaultvalue, $data
            ));
            break;

        case ADMIN_SETTING_TYPE_TEXT:
            // drop through to default clause
        default:
            $settings->add(new admin_setting_configtext(
                $uniquename, $displayname, $displayinfo, $defaultvalue
            ));
    }
}
