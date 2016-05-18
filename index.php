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
 * List of instances of wims module in the course
 *
 * @copyright  2015 Edunao SAS (contact@edunao.com)
 * @author     Sadge (daniel@edunao.com)
 * @package    mod_wims
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This is index.php - add code here to output a list of all of the instances of the module's component in the course

require('../../config.php');

$id = required_param('id', PARAM_INT); // course id

$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);

require_course_login($course, true);
$PAGE->set_pagelayout('incourse');

$params = array(
    'context' => context_course::instance($course->id)
);
$event = \mod_wims\event\course_module_instance_list_viewed::create($params);
$event->add_record_snapshot('course', $course);
$event->trigger();

$strinstance     = get_string('modulename', 'wims');
$strinstances    = get_string('modulenameplural', 'wims');
$strname         = get_string('name');

$PAGE->set_url('/mod/wims/index.php', array('id' => $course->id));
$PAGE->set_title($course->shortname.': '.$strinstances);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strinstances);
echo $OUTPUT->header();
echo $OUTPUT->heading($strinstances);

if (!$instances = get_all_instances_in_course('wims', $course)) {
    notice(get_string('thereareno', 'moodle', $strinstances), "$CFG->wwwroot/course/view.php?id=$course->id");
    exit;
}

$usesections = course_format_uses_sections($course->format);

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($usesections) {
    $strsectionname = get_string('sectionname', 'format_'.$course->format);
    $table->head  = array ($strsectionname, $strname);
    $table->align = array ('center', 'left');
} else {
    $table->head  = array ($strname);
    $table->align = array ('left');
}

$modinfo = get_fast_modinfo($course);
$currentsection = '';
foreach ($instances as $instance) {
    $cm = $modinfo->cms[$instance->coursemodule];
    if ($usesections) {
        $printsection = '';
        if ($instance->section !== $currentsection) {
            if ($instance->section) {
                $printsection = get_section_name($course, $instance->section);
            }
            if ($currentsection !== '') {
                $table->data[] = 'hr';
            }
            $currentsection = $instance->section;
        }
    }

    $extra = empty($cm->extra) ? '' : $cm->extra;
    $icon = '';
    if (!empty($cm->icon)) {
        $icon = '<img src="'.$OUTPUT->pix_url($cm->icon).'" class="activityicon" alt="'.get_string('modulename', $cm->modname).'" /> ';
    }

    $class = $instance->visible ? '' : 'class="dimmed"'; // hidden modules are dimmed
    $tabledata= array ();
    if ($usesections) {
         $tabledata[]=$printsection;
    }
    $tabledata[]="<a $class $extra href=\"view.php?id=$cm->id\">".$icon.format_string($url->name)."</a>";
    $table->data[]=$tabledata;
}

echo html_writer::table($table);

echo $OUTPUT->footer();
