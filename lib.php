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
 * Internal library classes.
 *
 * @package    qtype
 * @subpackage scripted
 * @copyright  2013 onwards Binghamton University
 * @author     Kyle J. Temkin <ktemkin@binghamton.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Ensure we have the formslib, which is required, as we're defining our own formslib component.
require_once('HTML/QuickForm/textarea.php');
require_once($CFG->libdir.'/formslib.php');

/**
 * Class which handles meta-data for the scripted question type.
 */ 
class qtype_scripted_plugin {
    
    /** @const string Specifies the path to the question type, relative to the moodle root. */
    const PATH = '/question/type/scripted';
}

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


//Bootstrap code which registers known languages is provided at the end of this file.

/**
 * Static class which manages the languages available to the scripted
 * language type, and acts as a factory for their creation.
 *
 * @copyright  2013 onwards Binghamton University
 * @author     Kyle J. Temkin <ktemkin@binghamton.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class qtype_scripted_language_manager {

    private static $languages = null;

    /**
    * Registers a known language.
    */
    public static function register_language($name) {
        self::$languages[] = $name;
    }

    /**
    * Returns a list of all available languages, including any files
    * required to use them.
    *
    * @return array A list of all applicable language names.
    */
    public static function get_available_languages() {
        //If we already have a memoized value, use it.
        if(self::$languages != null) { 
          return self::$languages;
        }
    }

    /**
    * Factory method which creates an interpreter designed to handle the specified language.
    */
    public static function create_interpreter($language, $variables = null, $functions = null ) {

        //If we weren't able to find the given language, raise an exception.
        if(!in_array($language, self::get_available_languages())) {
          throw new coding_exception(get_string('invalidlanguage', 'qtype_scripted'), get_string('invalidlanguage_debug', 'qtype_scripted', $language));
        }

        //Create a new interpreter, and return it.
        $class = 'qtype_scripted_language_'.$language;
        return new $class($variables, $functions);
    }

    /**
     * Executes the provided script, and returns its state: the script's return value, and a summary
     * of all variables and functions extant at the end of the script's execution.
     * 
     * @param string $language The name of the language which should be intepreted. Lua is preferred.
     * @param mixed $code The code to be executed.
     * @param array $vars Any variables which should be populated before this script is executed.
     * @param array $functions Any functions which should be created before this script is executed.
     * @access public
     * @return array    
     */
    public static function execute_script($language, $code, $vars = false, $functions = false) {

        //Create a scripting language interpreter.
        $interpreter = self::create_interpreter($language, $vars, $functions);

        //Execute the provided code...
        $return = $interpreter->execute($code);

        //Return the return value, the created varaibles, and the created functions.
        return array($return, $interpreter->get_variables(), $interpreter->get_functions());
    }

    /**
     * Evaluates all inline code and variables in a given block of text.
     * @param string $text The text to process.
     * @param string $language The name of the language which should be intepreted. Lua is preferred.
     * @param array $vars Any variables which should be populated before this script is executed.
     * @param array $functions Any functions which should be created before this script is executed.
     */
    public function format_text($text, $language, $vars, $functions)
    {
        // Evaluate all of the question's inline code.
        $operations = array(2 => 'execute', 1=> 'evaluate');
        foreach($operations as $bracket_level => $operation) {
          $interpreter = self::create_interpreter($language, $vars, $functions);
          $text = self::handle_inline_code($text, $bracket_level, $operation, $interpreter);
        }

        // And return the modified text.
        return $text;
    }

    /**
     * Evalutes inline-code (code surrounded in curly braces) provided as part of the question.
     *
     * @param string $text The question text to process.
     * @param string $match_level The amount of curly braces that should surround each block of inline code.
     * @param string $mode Can be either "evaluate" or "execute". Determines whether the code is evaluated (as an expression) or executed (as a block).
     * @param qtype_scripted_language $interpreter The interpreter which should be used to evaluate the inline code.
     * @param bool $show_errors If set, errors will be displayed inline.
     *
     * @return string The question text with the all inline code evaluated. Executed code is replaced by its "standard output"; while evaluated code is
     *     replaced by the result of the evaluated expressions.
     */
    private static function handle_inline_code($text, $match_level = 1, $mode = 'evaluate', $interpreter, $show_errors = false) {

      //Create a callback lambda which evaluates each block of inline code.
      $callback = function($matches) use($interpreter, $mode, $show_errors) {

        //Attempt to evaluate the given expression...
        try {
          return $interpreter->$mode($matches[1]); 
        }
        //... and insert a placeholder if the string fails.
        catch(qtype_scripted_language_exception $e) {

          //If show errors is on, display the exception directly...
          if($show_errors) {
            return '['.$e->getMessage().' in '.$matches[1].']';
          }
          //Otherwise, show a placeholder.
          else {
            return get_string('error_placeholder', 'qtype_scripted');
          }
        }
      };

      //Create a regular expression piece that matches the correct number
      //of open/close braces.
      $open_brace = str_repeat('\\{', $match_level);
      $close_brace = str_repeat('\\}', $match_level);

      //And replace each section in curly brackets with the evaluated version of that expression.
      return preg_replace_callback('/'.$open_brace.'(.*?)'.$close_brace.'/', $callback, $text);
    }
}

/** Base class for all scripting language exceptions. */
class qtype_scripted_language_exception extends exception {}

/**
 * Generic exception which can be used to wrap errors raised by
 * scripting langauges that do not extend qtype_scripted_language_exception.
 */ 
class qtype_scripted_language_interpreter_exception extends qtype_scripted_language_exception {

    public function __construct($wrapped_exception) {
        $this->inner_exception = $wrapped_exception; 
        $this->message = $this->inner_exception->getMessage();
    }

    public function __toString() {
        return $this->inner_exception->__toString();
    }
}

class qtype_scripted_invalid_result extends qtype_scripted_language_exception {}

/**
 * Base for a scripting language.
 *
 * @package    qtype
 * @subpackage scripted
 */
abstract class qtype_scripted_language {

  /**
   * Base constructor, from which all language constructors should extend.
   */
  public function __construct($variables = null, $functions = null) {
    //Set the relevant properties.
    $this->set_variables($variables); 
    $this->set_functions($functions); 
  }

  /**
   * Clears all of the provided variables.
   */ 
  public function initialize_variables($variable_names, $value = 0) {

    //Retrieve all variables known to the system.
    $vars = $this->get_variables();

    //If any of the provided variables have not been defined...
    foreach($variable_names as $name) {
      if(empty($vars[$name])) {

        //... set them to the provided value.
        $vars[$name] = $this->preprocess_value($value);
      }
    }

    //And set the system's state.
    $this->set_variables($vars);
  }

  /**
   * gets the value of a given variable.
   */
  public function get_variable($name) {
    $vars = $this->get_variables();
    return $vars[$name];
  }

  /**
   * convenience shortcut for get_variable().
   */
  public function __get($variable) {
    return $this->get_variable($variable);
  }

  /**
   * gets the value of a given variable.
   */
  public function set_variable($name, $value) {
    $vars = (array)$this->get_variables();
    $vars[$name] = $value;
    $vars = $this->set_variables($vars);
  }

  /**
   * convenience shortcut for set_variable().
   */
  public function __set($variable, $value) {
    return $this->set_variable($variable, $value);
  }

  /**
   * Hook which allows a variable's value to be formatted for the
   * target language. The default implementation passes the value unaltered.
   *
   * @param mixed $value The value to be processed.
   * @return mixed The value altered to the format used by the given language.
   */ 
  public function preprocess_value($value) {
    return $value;
  }

  /**
   * Extracts information from a raised exception.
   * Returns an associative array of known error information.
   *
   * Most languages should override this function.
   */
  public function error_information($exception) {
    return array('message' => $exception->getMessage());
  }

  /**
   * Returns a flat array summarizing all of the available variables.
   *
   * Each key represents a varaible name in a format suitable for use 
   * with get_variable(); each value represents the corresponding variable's
   * value.
   */
  public function summarize_variables() {
    return $this->get_variables();
  }

  /**
   * Gets an array that includes all function objects.
   * Only supported for languages for which functions aren't first-class objects.
   */
  public function get_functions() {
      return array();
  }
  
  /**
   * Sets all variables in the current interpreter's environment.
   * Only supported for languages in which functions aren't first-class objects.
   * 
   * @param array $values Associative array mapping name to value.
   */
  public function set_functions($values) {
  }

}



/**
 * Moodle QuickForm element for Scripted initialization scripts.
 */
class MoodleQuickForm_scripteditor extends HTML_QuickForm_textarea 
{
    // Store the path to the relevant CodeMirror instance.
    const CODEMIRROR_PATH =  '/question/type/scripted/scripts/codemirror';

    /**
     * @var stores the options for the question type
     */ 
    private $_options = array('language' => 'lua', 'highlighting' => 'text/x-lua');

	/**
	 * QuickForms constructor for the given waveform.
	 * 
	 * @param string $elementName
	 * @param string $elementLabel
	 * @param string $attributes
	 * @param unknown_type $options
	 */
	public function MoodleQuickForm_scripteditor($elementName=null, $elementLabel=null, $attributes=null, $options=null)
	{
		// Ensure we have an array of options.
		$options = $options ?: array();
		
		// And copy each relevant option into the QuickForm element.
        foreach ($options as $name=>$value)
            if (array_key_exists($name, $this->_options))
                $this->_options[$name] = $value;

        // Ensure that all of the editor's pre-requisites are included.
        $this->editor_head_setup();

        $attributes = 'wrap="virtual" rows="10" cols="60" style="font-family:monospace;' . $attributes;

		parent::HTML_QuickForm_element($elementName, $elementLabel, $attributes);
	}

	/**
	 * Static routine to insert the header code necessary start a CodeMirror instance, which should syntax highlight the initialization script.
	 */
	protected function editor_head_setup()
	{
		global $CFG, $PAGE;

        //Load the stylesheets required to render the CodeMirror instance...
        foreach(glob($CFG->dirroot . self::CODEMIRROR_PATH . '/*.css') as $file) {
            $PAGE->requires->css(self::CODEMIRROR_PATH . '/' . basename($file), true);
        }

        //And load each of its JavaScipt requirements.
        foreach(glob($CFG->dirroot . self::CODEMIRROR_PATH . '/*.js') as $file) {
            $PAGE->requires->js(self::CODEMIRROR_PATH . '/' . basename($file), true);
        }

    }

    /**
     * Core rendering routine for the scripted editor.
     * @return string The HTML that displays the relevant editor.
     */
	function toHtml()
	{
        global $PAGE;

        // Create the label for the editor.
        $html = html_writer::tag('label', $this->getLabel(), array('for' => $this->getAttribute('id'), 'class' => 'accesshide'));

        // Create the text area that the editor is based around.
        $html .= parent::toHtml();

        // Generate the pair of nested divs that will house a list of any errors encountered.
        $divid = $this->getAttribute('id').'_dynamicerrors';
        $div = html_writer::tag('div', '', array('class' => 'felement', 'id' => $divid));
        $html .= html_writer::tag('div', $div, array('class' => 'fitem'));

        $jsmodule = array(
            'name'     => 'qtype_scripted',
            'fullpath' => '/question/type/scripted/module.js',
            'requires' => array('node'),
            'strings' => array() //TODO: internationalize error checks?
        );

        //Get the options with which the editor should be initializes.
        $highlighting = $this->_options['highlighting'];
        $language = $this->_options['language'];

        //Initialize the syntax checker module.
        $PAGE->requires->js_init_call('M.qtype_scripted.init_dynamic', array($this->getAttribute('id'), $highlighting, $language), true);

        return $html;
	}

}


//Require all of the local language plugins.
foreach(glob(dirname(__FILE__)."/languages/*.php") as $file) {

  //Include the source file that contains the class definition...
  include_once($file);

  //And add the language to our list of known languages.
  qtype_scripted_language_manager::register_language(basename($file, '.php'));

}

//Register the script editor plugin type.
HTML_QuickForm::registerElementType('scripteditor', $CFG->dirroot.'/question/type/scripted/lib.php', 'MoodleQuickForm_scripteditor');







