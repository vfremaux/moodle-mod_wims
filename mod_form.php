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
 * Instance configuration formula for setting up new instances of the module
 *
 * @copyright  2015 Edunao SAS (contact@edunao.com)
 * @author     Sadge (daniel@edunao.com)
 * @package    mod_wims
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once ($CFG->dirroot.'/course/moodleform_mod.php');

class mod_wims_mod_form extends moodleform_mod {

    public function __construct($current, $section, $cm, $course) {
        // store away properties that we may need later
        $this->cm=$cm;
        $this->course=$course;
        // delegate to parent
        parent::__construct($current, $section, $cm, $course);
        // setup a global for use in the event hanler that catches the module rename event
        global $WIMS_MOD_FORM;
        $WIMS_MOD_FORM=true;
    }

    private function addtextfield($fieldnamebase,$maxlen,$defaultvalue=null,$fieldsuffix=''){
        $mform = $this->_form;
        $fieldname=$fieldnamebase.$fieldsuffix;
        $mform->addElement('text', $fieldname, get_string($fieldnamebase,'wims'), array('size'=>'60'));
        $mform->setType($fieldname, PARAM_TEXT);
        $mform->addRule($fieldname, null, 'required', null, 'client');
        $mform->addRule($fieldname, get_string('maximumchars', '', $maxlen), 'maxlength', $maxlen, 'client');
        if ($defaultvalue){
            $mform->setDefault($fieldname, $defaultvalue);
        }
    }
    
    private function addtextareafield($fieldnamebase,$defaultvalue=null,$fieldsuffix=''){
        $mform = $this->_form;
        $fieldname=$fieldnamebase.$fieldsuffix;
        $mform->addElement('textarea', $fieldname, get_string($fieldnamebase,'wims'), array('cols'=>'60','rows'=>'5'));
        $mform->setType($fieldname, PARAM_TEXT);
        if ($defaultvalue){
            $mform->setDefault($fieldname, $defaultvalue);
        }
    }

    private function addcheckbox($fieldnamebase,$defaultvalue=null,$fieldsuffix=''){
        $mform = $this->_form;
        $fieldname=$fieldnamebase.$fieldsuffix;
        $mform->addElement('checkbox', $fieldname, get_string($fieldnamebase,'wims'));
        $mform->setType($fieldname, PARAM_TEXT);
        if ($defaultvalue){
            $mform->setDefault($fieldname, $defaultvalue);
        }
    }

    function definition() {
        $mform = $this->_form;

        //-------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // text fields
        $this->addtextfield('name',255);
        $this->addtextfield('userfirstname',63);
        $this->addtextfield('userlastname',63);
        $this->addtextfield('username',63);
        $this->addtextfield('useremail',255);
        $this->addtextfield('userinstitution',127);
    }
    
    function definition_after_data(){
        $mform = $this->_form;

        //-------------------------------------------------------
        // if we have data from wims then use it
        if (property_exists($this,'configfromwims')===true){
        
            // treat all of the worksheets and then all of the exams
            foreach (array("worksheets","exams") as $sheettype){
                $sheettypestr=get_string('sheettype'.$sheettype,'wims');
                
                // for each sheet (whether worksheet or exam)
                foreach ($this->configfromwims[$sheettype] as $sheetidx => $sheetprops){
                    // work out the sheet status
                    switch($sheetprops['status']){
                        case '1': $statusstr=''; break;
                        case '2': $statusstr=get_string('wimsstatus2','wims'); break;
                        default : $statusstr=get_string('wimsstatusx','wims'); break;
                    }
                    if ($statusstr!==''){
                        $statusstr=' [ '.$statusstr.' ] ';
                    }
                    // split the 'graded' flag out from the title (if there is one)
                    $fulltitle=trim($sheetprops['title']);
                    if (substr($fulltitle,-1)=='*'){
                        $title=trim( substr($fulltitle,0,-1) );
                        $graded='1';
                    }else{
                        $title=$fulltitle;
                        $graded='0';
                    }
                    
                    // open a dedicated section for each sheet
                    $headerstr=$sheettypestr.$title.$statusstr;
                    $mform->addElement('header', 'sheetheader'.$sheettype.$sheetidx, $headerstr);
                    
                    // add title and 'graded' checkbox
                    $this->addtextfield('sheettitle',255,$title,$sheettype.$sheetidx);
                    if ($sheettype!='exams'){
                        $this->addcheckbox('sheetgraded',$graded,$sheettype.$sheetidx);
                    }
                    
                    // add an expiry date field
                    $datestr=$sheetprops['expiration'];
                    $dateobj=new DateTime($datestr,new DateTimeZone('UTC'));
                    $dateval=$dateobj->getTimestamp();
                    $expiryfieldname='sheetexpiry'.$sheettype.$sheetidx;
                    $mform->addElement('date_selector', $expiryfieldname, get_string('sheetexpiry', 'wims'), array('timezone'=>'UTC'));
                    $mform->setDefault($expiryfieldname, $dateval);
                }
            }
        }
        
        //-------------------------------------------------------
        $this->standard_coursemodule_elements();

        //-------------------------------------------------------
        $this->add_action_buttons();
    }

    private function updatedefaultvalue(&$default_values,$user,$propname,$fallback){
        $localkey="user".$propname;
        if (array_key_exists($localkey,$default_values) && $default_values[$localkey]!=""){
            // we have a non-empty value so don't change it
        }else if ($user->$propname!=""){
            $default_values[$localkey]=$user->$propname;
        }else{
            $default_values[$localkey]=$fallback;
        }
    }

    function data_preprocessing(&$default_values) {
        global $DB;
        global $USER;
        // prime the default values using the database entries that we've stored away
        $user=$DB->get_record('user',array('id'=>$USER->id));
        $config=get_config('wims');
        $this->updatedefaultvalue($default_values,$user,"firstname","anonymous");
        $this->updatedefaultvalue($default_values,$user,"lastname","supervisor");
        $this->updatedefaultvalue($default_values,$user,"email","noreply@wims.com");
        $this->updatedefaultvalue($default_values,$user,"institution",$config->defaultinstitution);
        if (!(array_key_exists("username",$default_values) && $default_values["username"]) ){
            $default_values["username"]=$default_values["userfirstname"]." ".$default_values["userlastname"];
        }
        // try to contact the WIMS server and see if the course already exists
        if (is_object($this->cm)){
            require_once(dirname(__FILE__).'/wimsinterface.class.php');
            $wims=new wims_interface($config,$config->debugsettings);
            $configfromwims=$wims->getclassconfigformodule($this->cm);
            $this->configfromwims=$configfromwims;
            // if the server sent us a config record then apply it
            if ($configfromwims){
                // check for a class name
                if (array_key_exists("description",$configfromwims)){
                    $default_values["name"]=$configfromwims["description"];
                }
                // process the rest of the parameters
                foreach ($configfromwims as $key=>$val){
                    $localkey="user".$key;
                    if (array_key_exists($localkey,$default_values)){
                        $default_values[$localkey]=$val;
                    }
                }
            }
        }
    }

    function validation($data, $files) {
        // if the course module has been instantiated already then put in an update request to wims
        if (is_object($this->cm)){
            // extract the properties that are of relevance for WIMS and organise them into a candidate data array
            $wimsdata=array(
                "description" => $data["name"],
                "institution" => $data["userinstitution"],
                "supervisor"  => $data["username"],
                "email"       => $data["useremail"],
                "lastname"    => $data["userlastname"],
                "firstname"   => $data["userfirstname"],
            );
            
            // copy out any data values that have changed into to the 'changed data' array
            $changeddata=array();
            foreach ($wimsdata as $key=> $val ){
                if ($this->configfromwims[$key] !== $val){
                    $changeddata[$key]=$val;
                }
            }
            
            // iterate over worksheets and exams
            if (property_exists($this,'configfromwims')===true){
                foreach (array("worksheets","exams") as $sheettype){
                    $changeddata[$sheettype]=array();
                    foreach ($this->configfromwims[$sheettype] as $sheetidx => $sheetprops){
                        // fetch parameters from data
                        $gradedkey='sheetgraded'.$sheettype.$sheetidx;
                        $title =$data['sheettitle'.$sheettype.$sheetidx];
                        $graded=array_key_exists($gradedkey,$data)? $data[$gradedkey]: '';
                        $expiry=$data['sheetexpiry'.$sheettype.$sheetidx];
                        // compose full title
                        $gradestr= ($graded==='1')? ' *': '';
                        $fulltitle= trim($title).$gradestr;
                        // compose the expiry date
                        $dateobj=new DateTime('@'.$expiry,new DateTimeZone('UTC'));
                        $expirydate=$dateobj->format('Ymd');
                        // determine whether anything has changed
                        $dirty=
                            ($sheetprops['title']!==$fulltitle)? true:
                            ($sheetprops['expiration']!==$expirydate)? true:
                            false;
                        // write the properties to the output data structure
                        if ($dirty===true){
                            $changeddata[$sheettype][$sheetidx]=array();
                            $changeddata[$sheettype][$sheetidx]['title']=$fulltitle;
                            $changeddata[$sheettype][$sheetidx]['expiration']=$expirydate;
                        }
                    }
                }
            }

            // put a call in to the wims server to update parameters
            require_once(dirname(__FILE__).'/wimsinterface.class.php');
            $config=get_config('wims');
            $wims=new wims_interface($config,$config->debugsettings);
            $wims->updateclassconfigformodule($this->cm,$changeddata);
        }
        // delegate to parent class
        $errors = parent::validation($data, $files);
        return $errors;
    }

}
