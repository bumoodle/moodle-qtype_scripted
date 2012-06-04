<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/scripted/question.php');
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

class qtype_scripted_edit_form extends qtype_shortanswer_edit_form //question_edit_form 
{

    /**
     * FIXME XXX FIXME
     * Replace all of the hardcoded HTML with $PAGE->requires references.
     */    
		
	/**
	 * Static routine to insert the header code necessary start a CodeMirror instance, which should syntax highlight the initialization script.
	 */
	static function editor_header()
	{
		global $CFG;
	
        //add the headers required for syntax highlighting in the init script
        //TODO: cleanup
		return  '
                        <script src="'.$CFG->wwwroot.'/question/type/scripted/scripts/codemirror/codemirror.js"></script>
			        	<script src="'.$CFG->wwwroot.'/question/type/scripted/scripts/codemirror/calcsane.js"></script>
						<link rel="stylesheet" href="'.$CFG->wwwroot.'/question/type/scripted/scripts/codemirror/codemirror.css">
						<link rel="stylesheet" href="'.$CFG->wwwroot.'/question/type/scripted/scripts/codemirror/default.css">
			        ';
	}
	
	/**
	 * Static routine to start a CodeMirror instance, which should syntax highlight the initialization script.
	 */
    static function editor_script($name, $editor_mode='text/calc-sane', $dyn_errors = true)
	{
		global $CFG;

        if($dyn_errors)
        {    
            return  '
                            <script src="'.$CFG->wwwroot.'/'.get_string('pluginname_link', 'qtype_scripted').'/dynerr_check.js" type="text/javascript"></script>
                            <script type="text/javascript">
                                var code;
                                var checkURI = "'.$CFG->wwwroot.'/'.get_string('pluginname_link', 'qtype_scripted').'/check_errors.php";
                                var options = { lineNumbers: true, mode: "'.$editor_mode.'", theme: "elegant", onKeyEvent: resetTimeout }
                                
                                    YUI().use("node", function(Y) { initscript = Y.DOM.byId("id_'.$name.'"); code = CodeMirror.fromTextArea(initscript, options); });
                            </script>
                                ';        
        }
        else
        {
            return '
			        	<script type="text/javascript">
			        		var code;
                            var options = { lineNumbers: true, mode: "'.$editor_mode.'", theme: "elegant", onKeyEvent: resetTimeout }
			        		
                                YUI().use("node", function(Y) { '.$name.' = Y.DOM.byId("id_'.$name.'"); CodeMirror.fromTextArea('.$name.', options); });
			        	</script>
            ';        

        }
	}
    
    /**
    * Add question-type specific form fields.
    *
    * @param MoodleQuickForm $mform the form being built.
    */
    function definition_inner(&$mform)
    {
    	global $CFG;

        //determine how the response will be interepteted (e.g. as a number)
    	$types = 
    	array(
    		qtype_scripted_response_mode::MODE_STRING  => get_string('resp_string', 'qtype_scripted'),
            qtype_scripted_response_mode::MODE_STRING_CASE_SENSITIVE  => get_string('resp_string_case', 'qtype_scripted'),
            qtype_scripted_response_mode::MODE_NUMERIC  => get_string('resp_numeric', 'qtype_scripted'),
            qtype_scripted_response_mode::MODE_HEXADECIMAL => get_string('resp_hexadecimal', 'qtype_scripted'),
            qtype_scripted_response_mode::MODE_BINARY => get_string('resp_binary', 'qtype_scripted'),
            qtype_scripted_response_mode::MODE_OCTAL => get_string('resp_octal', 'qtype_scripted')
    	);
    	$mform->addElement('select', 'response_mode', get_string('responseform', 'qtype_scripted'), $types);
    	
    	//prompt the user for simple answer or boolean expression evaluation
    	$types = array(
                    				 qtype_scripted_answer_mode::MODE_MUST_EQUAL  => get_string('eval_direct', 'qtype_scripted'),
                    				 qtype_scripted_answer_mode::MODE_MUST_EVAL_TRUE  => get_string('eval_boolean', 'qtype_scripted')
    	);
    	$mform->addElement('select', 'answer_mode', get_string('answerform', 'qtype_scripted'), $types);
    

        //insert the init-script editor
        self::insert_editor($mform);
        
        //add settings for interactive (and similar) modes
    	$this->add_interactive_settings();
       	
    	//allow more than one possible answer (including distractors)
    	$this->add_per_answer_fields($mform, get_string('answerno', 'qtype_scripted', '{no}'), question_bank::fraction_options(), 2, 2);
    	

    }

    /**
     * Helper function, which inserts a Scripted editor into the given question. 
     * Abstracted so it can be utilized by deriving classes, as well.
     * 
     * @param mixed $mform 
     * @return void
     */
    public static function insert_editor(&$mform, $name='init_code', $string=null, $header = true, $dyn_errors = true, $editor_mode = 'text/calc-sane')
    {
     	//$creategrades = get_grade_options();
    	
        //if we're to include a header, insert the header object
        if($header)
        {
            $mform->addElement('html', self::editor_header());
            $mform->addElement('header', 'optionsblock', get_string('options', 'qtype_scripted'));
        }

        //if no string was provided, use the phrase "initialization script" 
        if($string===null)
            $string = get_string('initscript', 'qtype_scripted');
    
    	//and prompt for the init script (possibly replace with code entry?)
    	$mform->addElement('textarea', $name, $string, 'wrap="virtual" rows="10" cols="60" style="font-family:monospace;"');
        $mform->addElement('html', self::editor_script($name, $editor_mode, $dyn_errors));

        if($dyn_errors)
        	$mform->addElement('html', '<div class="fitem"><div class="felement" id="dynamicerrors"></div></div>');

    }

    //TODO: validation should attempt execution and throw failure if required
   
    public function qtype() 
    {
        return 'scripted';
    }
}
