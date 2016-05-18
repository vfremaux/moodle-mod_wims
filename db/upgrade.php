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
 * wims module version upgrade code
 *
 * @copyright  2015 Edunao SAS (contact@edunao.com)
 * @author     Sadge (daniel@edunao.com)
 * @package    mod_wims
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * This file keeps track of upgrades to
 * the resource module
 *
 * Sometimes, changes between versions involve
 * alterations to database structures and other
 * major things that may break installations.
 *
 * The upgrade function in this file will attempt
 * to perform all the necessary actions to upgrade
 * your older installation to the current version.
 *
 * If there's something it cannot do itself, it
 * will tell you what you need to do.
 *
 * The commands in here will all be database-neutral,
 * using the methods of database_manager class
 *
 * Please do not forget to use upgrade_set_timeout()
 * before any action that may take longer time to finish.
 */

function xmldb_wims_addfield($dbman,$table,$name, $type=null, $precision=null, $unsigned=null, $notnull=null, $sequence=null, $default=null, $previous=null) {
    // instantiate a field object
    $field = new xmldb_field($name, $type, $precision, $unsigned, $notnull, $sequence, $default, $previous);
    // If the field doesn't already exist in the given table then add it
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }
}

function xmldb_wims_upgrade($oldversion) {
    global $CFG, $DB;
    $dbman = $DB->get_manager();
    $modulename='wims';

    // Upgrade to version with extra user... fields in database
    $nextversion=2015102201;
    if ($oldversion < $nextversion) {
        // Get hold of the module's database table
        $table = new xmldb_table($modulename);
        // Adding fields to table
        xmldb_wims_addfield($dbman,$table,'userinstitution', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL);
        xmldb_wims_addfield($dbman,$table,'userfirstname', XMLDB_TYPE_CHAR, '63', null, XMLDB_NOTNULL);
        xmldb_wims_addfield($dbman,$table,'userlastname', XMLDB_TYPE_CHAR, '63', null, XMLDB_NOTNULL);
        xmldb_wims_addfield($dbman,$table,'username', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL);
        xmldb_wims_addfield($dbman,$table,'useremail', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL);
        // Wims savepoint reached.
        upgrade_mod_savepoint(true, $nextversion, $modulename);
    }

    return true;
}
