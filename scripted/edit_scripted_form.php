<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/scripted/question.php');

/**
* Defines the editing form for the scripted question type.
*
* @package    qtype
* @subpackage scripted
* @copyright  2011 Binghamton University
* @author	   Kyle Temkin <ktemkin@binghamton.edu>
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

class qtype_scripted_edit_form extends question_edit_form 
{
	
	/*
    protected function definition_inner($mform) 
    {
        $menu = array(
            get_string('caseno', 'qtype_shortanswer'),
            get_string('caseyes', 'qtype_shortanswer')
        );
        $mform->addElement('select', 'usecase',
                get_string('casesensitive', 'qtype_shortanswer'), $menu);

        $mform->addElement('static', 'answersinstruct',
                get_string('correctanswers', 'qtype_shortanswer'),
                get_string('filloutoneanswer', 'qtype_shortanswer'));
        $mform->closeHeaderBefore('answersinstruct');

        $this->add_per_answer_fields($mform, get_string('answerno', 'qtype_shortanswer', '{no}'),
                question_bank::fraction_options());

        $this->add_interactive_settings();
    }*/
	
	/**
	 * Static routine to insert the header code necessary start a CodeMirror instance, which should syntax highlight the initialization script.
	 */
	static function editor_header()
	{
		global $CFG;
	
		//add the headers required for syntax highlighting in the init script
		return  '
			        	<script src="'.$CFG->wwwroot.'/scripts/codemirror/codemirror.js"></script>
			        	<script src="'.$CFG->wwwroot.'/scripts/codemirror/calcsane.js"></script>
						<link rel="stylesheet" href="'.$CFG->wwwroot.'/scripts/codemirror/codemirror.css">
						<link rel="stylesheet" href="'.$CFG->wwwroot.'/scripts/codemirror/default.css">
			        ';
	}
	
	/**
	 * Static routine to start a CodeMirror instance, which should syntax highlight the initialization script.
	 */
	static function editor_script()
	{
		global $CFG;
	
		return  '
			            <script src="'.$CFG->wwwroot.'/'.get_string('pluginname_link', 'qtype_scripted').'/dynerr_check.js" type="text/javascript"></script>
			        	<script type="text/javascript">
			        		var code;
			        		var checkURI = "'.$CFG->wwwroot.'/'.get_string('pluginname_link', 'qtype_scripted').'/check_errors.php";
							var options = { lineNumbers: true, mode: "text/calc-sane", theme: "elegant", onKeyEvent: resetTimeout }
			        		
			        		YUI().use("node", function(Y) { initscript = Y.DOM.byId("id_init_code"); code = CodeMirror.fromTextArea(initscript, options); });
			        	</script>
			        ';        
	}
    
    /**
    * Add question-type specific form fields.
    *
    * @param MoodleQuickForm $mform the form being built.
    */
    function definition_inner(&$mform)
    {
    	global $CFG;
    	 
    	//$creategrades = get_grade_options();
    	
    	$mform->addElement('html', self::editor_header());
    	$mform->addElement('header', 'optionsblock', get_string('options', 'qtype_scripted'));
    
    	//determine how the response will be interepteted (e.g. as a number)
    	$types = 
    	array(
    		qtype_scripted_response_mode::MODE_STRING  => get_string('resp_string', 'qtype_scripted'),
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
    
    	 
    
    	//and prompt for the init script (possibly replace with code entry?)
    	$mform->addElement('textarea', 'init_code', get_string('initscript', 'qtype_scripted'), 'wrap="virtual" rows="10" cols="60" style="font-family:monospace;"');
    	$mform->addElement('html', self::editor_script());
    	$mform->addElement('html', '<div class="fitem"><div class="felement" id="dynamicerrors"></div></div>');
    
    	//add settings for interactive (and similar) modes
    	$this->add_interactive_settings();
    	
    	//allow more than one possible answer (including distractors)
    	$this->add_per_answer_fields($mform, get_string('answerno', 'qtype_scripted', '{no}'), question_bank::fraction_options(), 2, 2);
    	

    }
    
    
    protected function data_preprocessing($question) 
    {
        $question = parent::data_preprocessing($question);
        $question = $this->data_preprocessing_answers($question);
        $question = $this->data_preprocessing_hints($question);

        return $question;
    }

    public function validation($data, $files) 
    {
        $errors = parent::validation($data, $files);
        $answers = $data['answer'];
        $answercount = 0;
        $maxgrade = false;
        foreach ($answers as $key => $answer) {
            $trimmedanswer = trim($answer);
            if ($trimmedanswer !== '') {
                $answercount++;
                if ($data['fraction'][$key] == 1) {
                    $maxgrade = true;
                }
            } else if ($data['fraction'][$key] != 0 ||
                    !html_is_blank($data['feedback'][$key]['text'])) {
                $errors["answer[$key]"] = get_string('answermustbegiven', 'qtype_shortanswer');
                $answercount++;
            }
        }
        if ($answercount==0) {
            $errors['answer[0]'] = get_string('notenoughanswers', 'qtype_shortanswer', 1);
        }
        if ($maxgrade == false) {
            $errors['fraction[0]'] = get_string('fractionsnomax', 'question');
        }
        return $errors;
    }

    public function qtype() 
    {
        return 'scripted';
    }
}
