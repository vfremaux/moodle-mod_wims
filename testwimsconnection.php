<?php
/**
 * wims interface test code
 *
 * @copyright  2015 Edunao SAS (contact@edunao.com)
 * @author     Sadge (daniel@edunao.com)
 * @package    mod_wims
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once("wimsinterface.class.php");

$config=new StdClass;
$config->serverurl="http://plateau-saclay.edunao.com:8081/wims/wims.cgi";
$config->serverpassword="MOODLE";
$config->institution="the_institution";
$config->supervisorname="the supervisor";
$config->contactemail="the.supervisor.email@edunao.com";
$config->lang="fr";
$config->qcloffset=100000;
$config->allowselfsigcerts=true;

$wimsdebug=true;
$wif=new wims_interface($config,$wimsdebug);

// start by establishing that the connection works
$connectionresult = $wif->testconnection();
echo "<pre>\n";
if ($connectionresult===true){
    echo "Connection OK\n";
}else{
    echo "Connection FAILED:\n";
    foreach ($wif->errormsgs as $msg){
        echo "&gt; $msg\n";
    }
}
echo "</pre>\n";
