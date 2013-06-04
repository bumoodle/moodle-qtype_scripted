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

    //TODO: Abstract the base path.
    static $codemirror_path =  '/question/type/scripted/scripts/codemirror';
	
	/**
	 * Static routine to insert the header code necessary start a CodeMirror instance, which should syntax highlight the initialization script.
	 */
	protected function add_editor_header()
	{
		global $CFG, $PAGE;

        //Load the stylesheets required to render the CodeMirror instance...
        foreach(glob($CFG->dirroot . self::$codemirror_path . '/*.css') as $file) {
            $PAGE->requires->css(self::$codemirror_path . '/' . basename($file), true);
        }

        //And load each of its JavaScipt requirements.
        foreach(glob($CFG->dirroot . self::$codemirror_path . '/*.js') as $file) {
            $PAGE->requires->js(self::$codemirror_path . '/' . basename($file), true);
        }
    }
	
	/**
	 * Static routine to start a CodeMirror instance, which should syntax highlight the initialization script.
	 */
    static function editor_script($name, $editor_mode='text/x-lua', $dyn_errors = true)
    {
		global $CFG, $PAGE;

        $jsmodule = array(
            'name'     => 'qtype_scripted',
            'fullpath' => '/question/type/scripted/module.js',
            'requires' => array('node'),
            'strings' => array() //TODO: internationalize error checks?
        );

        //Determine which ininitialization function should be called.
        $error_mode = $dyn_errors ? 'dynamic' : 'static';

        //Initialize the syntax checker module.
        $PAGE->requires->js_init_call('M.qtype_scripted.init_'.$error_mode, array('id_'.$name, $editor_mode, 'lua'), true);
            
        return '';
	}
    
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
        $this->insert_editor();
        
        //add settings for interactive (and similar) modes
    	$this->add_interactive_settings();
       	
    	//allow more than one possible answer (including distractors)
    	$this->add_per_answer_fields($mform, get_string('answerno', 'qtype_scripted', '{no}'), question_bank::fraction_options(), 2, 2);
    }

    /**
     * Helper function, which inserts a Scripted editor into the given question. 
     * 
     * @param mixed $mform 
     * @return void
     */
    public function insert_editor($name='init_code', $string=null, $header = true, $dyn_errors = true, $editor_mode = 'text/x-lua')
    {
     	//$creategrades = get_grade_options();
    	
        //if we're to include a header, insert the header object
        if($header)
        {
            self::add_editor_header();
            $this->_form->addElement('header', 'optionsblock', get_string('options', 'qtype_scripted'));
            $this->_form->setExpanded('optionsblock');
        }

        //if no string was provided, use the phrase "initialization script" 
        if($string===null)
            $string = get_string('initscript', 'qtype_scripted');
    
    	//and prompt for the init script (possibly replace with code entry?)
    	$this->_form->addElement('textarea', $name, $string, 'wrap="virtual" rows="10" cols="60" style="font-family:monospace;"');
        $this->_form->addElement('html', self::editor_script($name, $editor_mode, $dyn_errors));

        if($dyn_errors) {
           $this->add_dynamic_error_output('dynamicerrors'); 
        }
    }

    /**
     * Creates an HTML form element where the 
     */ 
    private function add_dynamic_error_output($id) {

        //Generate the pair of nested divs that will house a list of any errors encountered.
        $div = html_writer::tag('div', '', array('class' => 'felement', 'id' => $id));
        $html = html_writer::tag('div', $div, array('class' => 'fitem'));

        //Create and return a new HTML for element.
        return $this->_form->addElement('html', $html);
    }

    //TODO: validation should attempt execution and throw failure if required
   
    public function qtype() 
    {
        return 'scripted';
    }
}
