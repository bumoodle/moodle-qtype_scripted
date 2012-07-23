<?php


defined('MOODLE_INTERNAL') || die();


/**
* 
*
* @package    qtype
* @subpackage scripted
* @copyright  2011 Binghamton University
* @author	   Kyle Temkin <ktemkin@binghamton.edu>
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
function qtype_scripted_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) 
{
    global $DB, $CFG;
    require_once($CFG->libdir . '/questionlib.php');
    question_pluginfile($course, $context, 'qtype_scripted', $filearea, $args, $forcedownload);
}
