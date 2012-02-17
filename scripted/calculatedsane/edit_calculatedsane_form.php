<?php

// Boolean Question Type
// (C) Binghamton University 2011
//
// author: Kyle Temkin <ktemkin@binghamton.edu>
//
// Modified from Moodle Core Code

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the editing form for the shortanswer question type.
 *
 * @package   qtype_boolean
 * @copyright 2011 Binghamton University
 * @author 	  Kyle Temkin <ktemkin@binghamton.edu>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * shortanswer editing form definition.
 */
class question_edit_calculatedsane_form extends question_edit_form 
{
	

    function get_per_answer_fields(&$mform, $label, $gradeoptions, &$repeatedoptions, &$answersoption) 
    {
    	
    	
     	$repeated = array();
        
     	//set up the per-option answers
     	$repeated[] =& $mform->createElement('header', 'answerhdr', $label);
        $repeated[] =& $mform->createElement('text', 'answer', get_string('answer', 'quiz'), array('size' => 80));
        $repeated[] =& $mform->createElement('select', 'fraction', get_string('grade'), $gradeoptions);
        $repeated[] =& $mform->createElement('editor', 'feedback', get_string('feedback', 'quiz'),array('rows' => 5), $this->editoroptions);
        
        
        
        $repeatedoptions['answer']['type'] = PARAM_RAW;
        $repeatedoptions['fraction']['default'] = 0;
        $answersoption = 'answers';
        return $repeated;   
    }
    
	
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
	
	static function editor_script()
	{
		global $CFG;
		
		return  '
		            <script src="'.$CFG->wwwroot.'/'.get_string('calculatedsane_link', 'qtype_calculatedsane').'/dynerr_check.js" type="text/javascript"></script>
		        	<script type="text/javascript">
		        		var code;
		        		var checkURI = "'.$CFG->wwwroot.'/'.get_string('calculatedsane_link', 'qtype_calculatedsane').'/check_errors.php";
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
    	
        $creategrades = get_grade_options();
        
		

        $mform->addElement('html', self::editor_header());
        $mform->addElement('header', 'optionsblock', get_string('options', 'qtype_calculatedsane'));
        
        //prompt the user for how the student's answer should be interpreted
        $types = array(
                                				'direct' => get_string('eval_direct', 'qtype_calculatedsane'),
                                				'boolean' => get_string('eval_boolean', 'qtype_calculatedsane')
        );
        $mform->addElement('select', 'answer_mode', get_string('answerform', 'qtype_calculatedsane'), $types);
        
        //prompt the user for simple answer or boolean expression evaluation
        $types = array(
                				'direct' => get_string('eval_direct', 'qtype_calculatedsane'),
                				'boolean' => get_string('eval_boolean', 'qtype_calculatedsane')
        );
        $mform->addElement('select', 'answer_mode', get_string('answerform', 'qtype_calculatedsane'), $types);
        
       

        
        
        //and prompt for the init script (possibly replace with code entry?)
        $mform->addElement('textarea', 'init_code', get_string('initscript', 'qtype_calculatedsane'), 'wrap="virtual" rows="10" cols="60" style="font-family:monospace;"');
        $mform->addElement('html', self::editor_script());
        $mform->addElement('html', '<div class="fitem"><div class="felement" id="dynamicerrors"></div></div>');
        
        //allow more than one boolean expression as an answer
        $this->add_per_answer_fields($mform, get_string('answerno', 'qtype_boolean', '{no}'), $creategrades->gradeoptions, 2, 2);
    }

    function data_preprocessing($question) 
    {
        if (isset($question->options))
        {
            $answers = $question->options->answers;
            $answers_ids = array();
            
            if (count($answers)) 
            {
                $key = 0;
                foreach ($answers as $answer){
                    $answers_ids[] = $answer->id;
                    $default_values['answer['.$key.']'] = $answer->answer;
                    $default_values['fraction['.$key.']'] = $answer->fraction;
                    $default_values['feedback['.$key.']'] = array();

                    // prepare feedback editor to display files in draft area
                    $draftid_editor = file_get_submitted_draft_itemid('feedback['.$key.']');
                    $default_values['feedback['.$key.']']['text'] = file_prepare_draft_area(
                        $draftid_editor,       // draftid
                        $this->context->id,    // context
                        'question',   // component
                        'answerfeedback',             // filarea
                        !empty($answer->id)?(int)$answer->id:null, // itemid
                        $this->fileoptions,    // options
                        $answer->feedback      // text
                    );
                    $default_values['feedback['.$key.']']['itemid'] = $draftid_editor;
                    // prepare files code block ends

                    $default_values['feedback['.$key.']']['format'] = $answer->feedbackformat;
                    $key++;
                }
                
                
            }
            //$default_values['usecase'] = $question->options->usecase;
            $question = (object)((array)$question + $default_values);
        }
        return $question;
    }
    
    function validation($data, $files) 
    {
    	
        $errors = parent::validation($data, $files);
        $answers = $data['answer'];
        $answercount = 0;
        $maxgrade = false;
        
                
        //for each of the supplied answer
        foreach ($answers as $key => $answer) 
        {
        	//trim post/preceeding whitespcae
            $trimmedanswer = trim($answer);
            
            //if an answer was provided
            if ($trimmedanswer !== '')
            {
            	//count it
                $answercount++;

				//if this grade counts as 100%
                if ($data['fraction'][$key] == 1)
                {
					//set the maxgrade variable, which indicates that there's at least
					//one full-credit answer  
                    $maxgrade = true;
                }
                    
            } 
            //otherwise, 
            else if ($data['fraction'][$key] != 0 || !html_is_blank($data['feedback'][$key]['text'])) 
            {
                $errors["answer[$key]"] = get_string('answermustbegiven', 'qtype_shortanswer');
                $answercount++;
            }
        }
        
        if ($answercount==0)
            $errors['answer[0]'] = get_string('notenoughanswers', 'quiz', 1);
        
        if ($maxgrade == false) 
            $errors['fraction[0]'] = get_string('fractionsnomax', 'question');

        return $errors;
    }
    
    function qtype() 
    {
        return 'calculatedsane';
    }
}