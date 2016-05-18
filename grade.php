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
 * Respond to gradebook title click
 *
 * @copyright  2016 Edunao SAS (contact@edunao.com)
 * @author     Sadge (daniel@edunao.com)
 * @package    mod_wims
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This is grade.php - it is called up by moodle when the user clicks on a hgradebook column title


///////////////////////////////////////////////////////////////////////////
// includes

require('../../config.php');
require_once(dirname(__FILE__).'/wimsinterface.class.php');


///////////////////////////////////////////////////////////////////////////
// _GET / _POST parameters

$id         = required_param('id', PARAM_INT);           // Course module ID
$itemnumber = required_param('itemnumber', PARAM_INT);   // The grade column that was clicked - identifies the exam, worksheet, etc from which we come
$userid     = optional_param('userid', 0, PARAM_INT);    // Graded user ID (optional)


///////////////////////////////////////////////////////////////////////////
// Lookup configuration from moodle

$config = get_config('wims');


///////////////////////////////////////////////////////////////////////////
// Construct the arguments for the URL

$urlargs = array( 'id' => $id );

define( 'WORKSHEET_ID_OFFSET', 1000 );
if ($config->usegradepage==1){
    // direct the user to the grade page
    $urlargs['wimspage']    = WIMS_GRADE_PAGE;
}elseif ($itemnumber>=WORKSHEET_ID_OFFSET){
    // direct the user to a specific worksheet
    $urlargs['wimspage']    = WIMS_WORKSHEET;
    $urlargs['wimsidx']     = $itemnumber - WORKSHEET_ID_OFFSET;
}else{
    // direct the user to a specific exam
    $urlargs['wimspage']    = WIMS_EXAM;
    $urlargs['wimsidx']     = $itemnumber;
}


///////////////////////////////////////////////////////////////////////////
// Delegate to view.php page which will look after redirecting to WIMS

redirect( new moodle_url( '/mod/wims/view.php', $urlargs ) );

