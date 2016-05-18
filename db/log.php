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
 * Definition of log events for module wims
 *
 * @copyright  2015 Edunao SAS (contact@edunao.com)
 * @author     Sadge (daniel@edunao.com)
 * @package    mod_wims
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


// add log event defintins here
$logs = array(
    array('module'=>'wims', 'action'=>'view', 'mtable'=>'wims', 'field'=>'name'),
    array('module'=>'wims', 'action'=>'view all', 'mtable'=>'wims', 'field'=>'name'),
);

