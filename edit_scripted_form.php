<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/scripted/lib.php');
require_once($CFG->dirroot . '/question/type/shortanswer/edit_shortanswer_form.php');

/**
* Defines the editing form for the scripted question type.
*
* @package    qtype
* @subpackage scripted
* @copyright  2011 Binghamton University
* @author	   Kyle Temkin <ktemkin@binghamton.edu>
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

class qtype_scripted_edit_form extends qtype_shortanswer_edit_form {
    
    /**
    * Add question-type specific form fields.
    *
    * @param MoodleQuickForm $mform the form being built.
    */
    protected function definition_inner($mform) {
    	global $CFG;

        //determine how the response will be interepteted (e.g. as a number)
    	$types = array(
            qtype_scripted_response_mode::MODE_STRING  => get_string('resp_string', 'qtype_scripted'),
            qtype_scripted_response_mode::MODE_STRING_CASE_SENSITIVE  => get_string('resp_string_case', 'qtype_scripted'),
            qtype_scripted_response_mode::MODE_NUMERIC  => get_string('resp_numeric', 'qtype_scripted'),
            qtype_scripted_response_mode::MODE_HEXADECIMAL => get_string('resp_hexadecimal', 'qtype_scripted'),
            qtype_scripted_response_mode::MODE_BINARY => get_string('resp_binary', 'qtype_scripted'),
            qtype_scripted_response_mode::MODE_OCTAL => get_string('resp_octal', 'qtype_scripted')
    	);
    	$mform->addElement('select', 'response_mode', get_string('responseform', 'qtype_scripted'), $types);

        //prompt the user for the scripting language to work with
        $languages = array(
            'lua' => get_string('lua', 'qtype_scripted'),
            'mathscript' => get_string('mathscript', 'qtype_scripted')
        );
        $mform->addElement('select', 'language', get_string('language', 'qtype_scripted'), $languages);

    	//prompt the user for simple answer or boolean expression evaluation
    	$types = array(
         qtype_scripted_answer_mode::MODE_MUST_EQUAL  => get_string('eval_direct', 'qtype_scripted'),
         qtype_scripted_answer_mode::MODE_MUST_EVAL_TRUE  => get_string('eval_boolean', 'qtype_scripted')
    	);
        $mform->addElement('select', 'answer_mode', get_string('answerform', 'qtype_scripted'), $types);

        //Render the 
        $mform->addElement('scripteditor', 'init_code', get_string('initscript', 'qtype_scripted'));
        
        //add settings for interactive (and similar) modes
    	$this->add_interactive_settings();
       	
    	//allow more than one possible answer (including distractors)
    	$this->add_per_answer_fields($mform, get_string('answerno', 'qtype_scripted', '{no}'), question_bank::fraction_options(), 2, 2);
    }

    //TODO: validation should attempt execution and throw failure if required
   
    public function qtype() 
    {
        return 'scripted';
    }
}
