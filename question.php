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
 * Scripted question type.
 *
 * @package    qtype
 * @subpackage scripted
 * @copyright  2013 onwards Binghamton University
 * @author     Kyle J. Temkin <ktemkin@binghamton.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/question/type/scripted/locallib.php');

class qtype_scripted_response_mode {

    /** *  Interpret the user's response as a string, which may be numeric, if MODE_MUST_EQUAL is selected.  */
    const MODE_STRING = 0;

    /** *  Interpret the user's response as a _case sensitive_ string.  */
    const MODE_STRING_CASE_SENSITIVE = 5;

    /** * Interpret the user's response as a floating point number, which is base ten.  */
    const MODE_NUMERIC = 1;

    /** * Interpret the user's response as a binary number, which is converted to an integer before checking.  */
    const MODE_BINARY =  2;

    /** * Interpret the user's response as a hexadecimal number, which is converted to an integer before checking.  */
    const MODE_HEXADECIMAL = 3;

    /** * Interpret the user's response as an octal number, which is converted to an integer before checking.  */
    const MODE_OCTAL = 4;
}

class qtype_scripted_answer_mode {
    /** * True to indciate that a correct response must be equal (using PHP's == if both answers are numeric, and === otheriwse) to the evaluated answer.  */
    const MODE_MUST_EQUAL = 0;
    
    /** * True to indicate that the evaluated answer (which will depend on the user's response) must be nonzero.  */
    const MODE_MUST_EVAL_TRUE = 2;
}

/**
* Defines a Scripted question, a variant of the short-answer question whose answers are determined programatically.
* This is mostly intended for STEM fields, where instructors a likely to know at least a little bit of programming.
*
* @package    qtype
* @subpackage scripted
* @copyright  2011, 2013 Binghamton University
* @author     Kyle Temkin <ktemkin@binghamton.edu>
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
class qtype_scripted_question extends question_graded_by_strategy implements question_response_answer_comparer 
{
    /** @var int (member of answer_mode) Indicates the grading mechanism used to compare answers . */
    public $answer_mode;
    
    /** @var string A short script to be executed upon the instantiation of a new question. */
    public $init_code;

    /** @var int (member of qtype_scripted_response_mode)   A short identifer which determines how this question will be graded. */
    public $reponse_mode;
    
    /** @var array of question_answer. */
    public $answers = array();
    
    /** @var array - an array containing the variables created by the initialization script */
    private $vars;
    
    /** @var array - an array containing the functions defined by the initialization script */
    private $funcs;

    /** @var string The scripting language which is used for evaluting the init script. */
    public $language;



    /**
     * Creates a new Scripted question instance.
     */
    public function __construct() {
        parent::__construct(new question_first_matching_answer_grading_strategy($this));
    }

    /**
     * Indicates the type of answer data which Moodle expects to recieve; the scripted quesiton type
     * always recieves the same, single answer.
     */
    public function get_expected_data() {
        return array('answer' => PARAM_RAW_TRIMMED);
    }

    
    /**
    * Start a new attempt at this question, storing any information that will
    * be needed later in the step.
    *
    * In this case, the initialization script is called once; it defines a series of 
    * functions and variables which can be used by the user response.
    *
    * @param question_attempt_step The first step of the {@link question_attempt}
    *      being started. Can be used to store state.
    * @param int $varant which variant of this question to start. Will be between
    *      1 and {@link get_num_variants()} inclusive.
    */
    public function start_attempt(question_attempt_step $step, $variant) {
        //evaluate the initialization script, ensuring that all variables defined in the question text are initialized
        list(, $vars, $funcs) = self::execute_script($this->init_code, $this->questiontext, null, null, $this->language);

        //and apply the result of the code
        $this->apply_code_result($step, $vars, $funcs);
    }

    /**
     * Applies the result of a given script to the given step. 
     * Used to handle the result of the initialization script, as well as by extension question types such as Multianswer.
     * 
     * @param question_attempt_step $step   The question attempt step to be populated. 
     * @param array $vars                   An associative array containing variable definitions.
     * @param array $funcs                  An associative array, which contains function definitions.
     * @return void
     */
    public function apply_code_result(question_attempt_step $step, array $vars, array $funcs) {
        //store the list of variables after the execution, for storage in the database
        $step->set_qt_var('_vars',  self::safe_serialize($vars));
        $step->set_qt_var('_funcs', self::safe_serialize($funcs));
        
        //store a local copy of the script state
        $this->vars = $vars;
        $this->funcs = $funcs;
    }

    /**
     * Executes the provided script, and returns its state: the script's return value, and a summary
     * of all variables and functions extant at the end of the script's execution.
     * 
     * @deprecated Use $this->create_interpreter() and friends instead.
     * @param mixed $code The code to be executed.
     * @param string $question_text Any question text which is going to be processed in conjunction with this script.
     * @param array $vars Any variables which should be populated before this script is executed.
     * @param array $functions Any functions which should be created before this script is executed.
     * @param string $language The name of the language which should be intepreted. Lua is preferred.
     * @access public
     * @return array    
     */
    public static function execute_script($code, $question_text = false, $vars = false, $functions = false, $language='lua') {
        //Create a scripting language interpreter.
        $interpreter = qtype_scripted_language_manager::create_interpreter($language, $vars, $functions);

        //Execute the provided code...
        $return = $interpreter->execute($code);

        //Return the return value, the created varaibles, and the created functions.
        return array($return, $interpreter->get_variables(), $interpreter->get_functions());
    }

    /**
     * (non-PHPdoc)
     * @see question_definition::apply_attempt_state()
     */
    public function apply_attempt_state(question_attempt_step $step) {

        //Restore the serialized variables and functions.
        $this->vars = self::safe_unserialize($step->get_qt_var('_vars'));
        $this->funcs = self::safe_unserialize($step->get_qt_var('_funcs'));
    }
    
    
    /**
     * Summarizes a student response for the review (e.g. the Review Attempt view). 
     */
    public function summarise_response(array $response) {
        //If an answer has been provided, return it.
        return isset($response['answer']) ? $response['answer'] : null;
    }

    /***
     * Returns true if the given response is considered valid or complete.
     */
    public function is_complete_response(array $response) {

        //a response without an answer is not a compelte response    
        if(!array_key_exists('answer', $response)) {
            return false;
        }

        //determine gradability based on response type
        switch($this->response_mode) {

            //in string mode, accept any non-empty string
            case qtype_scripted_response_mode::MODE_STRING:
            case qtype_scripted_response_mode::MODE_STRING_CASE_SENSITIVE:
                return $response['answer'] !== '';

            //in numeric mode, accept any numeric string
            case qtype_scripted_response_mode::MODE_NUMERIC:
                return is_numeric($response['answer']);

            //in binary mode, check to see if the number is valid binary using a regex
            case qtype_scripted_response_mode::MODE_BINARY:
                return preg_match('#^(0b|\%)?[01]+$#', $response['answer']) !== 0 || (array_key_exists('answer', $response) && empty($response['answer']));

            //do the same for hexadecimal
            case qtype_scripted_response_mode::MODE_HEXADECIMAL:
                return preg_match('#^(0x|\$)?[0-9a-fA-F]+$#', $response['answer']) !== 0;

            //do the same for octal 
            case qtype_scripted_response_mode::MODE_OCTAL:
                return preg_match('#^(0o|\@)?[0-7]+$#', $response['answer']) !== 0;
        }
    }

    /***
     * Returns an error message, in case the response won't validate. 
     */
    public function get_validation_error(array $response) {

        //if the string was gradeable, don't indicate an error
        if ($this->is_gradable_response($response)) {
            return '';
        }

        //otherwise, indicate an error depending on the type of reponse which was expected
        switch($this->response_mode) {
            case qtype_scripted_response_mode::MODE_NUMERIC:
                return get_string('invalid_numeric', 'qtype_scripted');
            case qtype_scripted_response_mode::MODE_BINARY:
                return get_string('invalid_binary', 'qtype_scripted');
            case qtype_scripted_response_mode::MODE_HEXADECIMAL:
                return get_string('invalid_hexadecimal', 'qtype_scripted');
            case qtype_scripted_response_mode::MODE_OCTAL:
                return get_string('invalid_octal', 'qtype_scripted');
        }

        //if we were unable to determine the correct message, fall back on the normal "please enter an answer" gracefully
        return get_string('pleaseenterananswer', 'qtype_shortanswer');
    }

    /**
     * Returns true iff the two responses are identical.
     */
    public function is_same_response(array $prevresponse, array $newresponse) {
        return question_utils::arrays_same_at_key_missing_is_blank($prevresponse, $newresponse, 'answer');
    }

    /**
     * Returns all possible answers (including distractors) for the given question.
     */
    public function get_answers() {
        return $this->answers;
    }

    /**
     * Compares a given response with a given answer; the way which this is performed is determined by the
     * answer_mode variable.
     */
    public function compare_response_with_answer(array $response, question_answer $answer) {      

        //parse the response according to the selected response mode
        $value = $this->parse_response($response);
    
        //Create a new interpreter using the serialized question state.
        $interpreter = $this->create_interpreter($this->vars, $this->funcs);

        //Process the answer according to the interpretation mode. 
        switch($this->answer_mode)
        {
            //for direct/answer modes,
            case qtype_scripted_answer_mode::MODE_MUST_EQUAL:
        
                //evaluate the given answer formula
                $ans = $interpreter->evaluate($answer->answer);
    
                //if we're comparing in a non-case-sensitive manner, convert the _answer_ to lowercase
                if($this->response_mode === qtype_scripted_response_mode::MODE_STRING) {
                    $ans = strtolower($ans);
                }

                 //if the two are both numeric, compare them loosely, without regard to type; so 5 == "05" is true
                if(is_numeric($ans) && is_numeric($value)) {
                    return $ans == $value;
                }
                //otherwise, compare them stricly; so "5a" !== 5; (note that, had we performed a loose compare, "5a" == 5 is true due to type juggling >.<)
                else {
                    return $ans === $value;
                }
        
            //case 'boolean':
            case qtype_scripted_answer_mode::MODE_MUST_EVAL_TRUE:
        
                //Define the variable "resp" in the context of the interpreter.
                //Also define response, as per the principle of least astonishment.
                $interpreter->resp = $value;
                $interpreter->response = $value;

                try {
                    //and return true iff the answer evaluates to True
                    return (bool)$interpreter->evaluate($answer->answer);
                }
                //If an error occurs during evalution, return false.
                catch(qtype_scripted_language_exception $e) {
                    return false;
                }
        
            default:
        
                //something's gone wrong
                throw new coding_exception('Invalid grading mode for the scripted qtype.');
        
        }
    }

    /**
     * Determines which question-related files the user is allowed to access.
     * 
     * In our case, the user should be able to view the question, and its hints.
     */
    public function check_file_access($qa, $options, $component, $filearea, $args, $forcedownload) {

        if ($component == 'question' && $filearea == 'answerfeedback') {

            $currentanswer = $qa->get_last_qt_var('answer');
            $answer = $qa->get_question()->get_matching_answer(array('answer' => $currentanswer));
            $answerid = reset($args); // itemid is answer id.

            return $options->feedback && $answerid == $answer->id;

        } 
        else if ($component == 'question' && $filearea == 'hint') 
        {
            return $this->check_hint_file_access($qa, $options, $args);

        }
        else 
        {
            return parent::check_file_access($qa, $options, $component, $filearea,
                    $args, $forcedownload);
        }
    }
    
    /**
     * Inserts the varaibles for the given question text, then calls the basic formatter.
     */
    public function format_questiontext($qa)
    {
        //get a list of varaibles created by the initialization script 
        $vars = self::safe_unserialize($qa->get_last_qt_var('_vars'));

        //execute any code in double brackets _first_
        $questiontext = $this->questiontext;

        //Evaluate all of the question's inline code.
        $operations = array(2 => 'execute', 1=> 'evaluate');
        foreach($operations as $bracket_level => $operation) {
          $interpreter = $this->create_interpreter($this->vars, $this->funcs);
          $questiontext = $this->handle_inline_code($questiontext, $bracket_level, $operation, $interpreter);
        }

        //run the question text through the basic moodle formatting engine
        return $this->format_text($questiontext, $this->questiontextformat, $qa, 'question', 'questiontext', $this->id);
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
    public static function handle_inline_code($text, $match_level = 1, $mode = 'evaluate', $interpreter, $show_errors = false) {

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

    /**
     * Parses the user response, returning a simple answer. 
     * The way in which the user's response is parsed depends on the Response Mode.
     *
     * @param array $response The user's response object.
     */
    private function parse_response(array $response)
    {
        //strip all leading and trailing whitespace from the answer
        $response['answer'] = trim($response['answer']);

        //interpret the user's reponse according to the reponse mode
        switch($this->response_mode)
        {
            
            //handle STRING-mode respones
            case qtype_scripted_response_mode::MODE_STRING:            

                //return the answer as-is, as we already recieved a string
                return strtolower($response['answer']);

                //handle STRING-mode respones
            case qtype_scripted_response_mode::MODE_STRING_CASE_SENSITIVE:            

                //return the answer as-is, as we already recieved a string
                return $response['answer'];

            //handle DECIMAL-mode responses
            case qtype_scripted_response_mode::MODE_NUMERIC:

                //if the string was empty, return false, a non-numeric form of zero
                if($response['answer'] === '')
                    return false;

                //get a floating-point interpretation of the answer
                return floatval($response['answer']);

            
            //handle HEXADECIMAL-mode responses 
            case qtype_scripted_response_mode::MODE_HEXADECIMAL:

                //if the user entered a number in C format, parse it using PHP's native recognition of hex numbers
                if(substr($response['answer'], 0, 2) === "0x")
                    return intval(substr($response['answer'], 2), 16);

                //if the user entered the hex number in HCS08 format (i.e. $0F), accept that, as well
                elseif(substr($response['answer'], 0, 1) == '$')
                    return hexdec(substr($response['answer'], 1));

                //otherwise, return the answer parsed as a hex number
                else
                    return hexdec($response['answer']);

            //handle BINARY-mode respones
            case qtype_scripted_response_mode::MODE_BINARY:

                //if the user entered a number in 0b format (used by some calculators), accept it  
                if(substr($response['answer'], 0, 2) === "0b")
                    return bindec(substr($response['answer'], 2), 16);

                //if the user entered the binary number in HCS08 format (i.e. %0F), accept that, as well
                elseif(substr($response['answer'], 0, 1) == '%')
                    return bindec(substr($response['answer'], 1));

                //otherwise, return the answer parsed as a binary number
                else
                    return bindec($response['answer']);


            //handle OCTAL-mode
            case qtype_scripted_response_mode::MODE_OCTAL:

                //if the user entered a number in 0o format, accept it, for consistency with other prefix strings
                //(as far as I know, no major format uses this)
                if(substr($response['answer'], 0, 2) === "0o")
                    return octdec(substr($response['answer'], 2), 16);

                //if the user entered the binary number in HCS08 format (i.e. @0F), accept that, as well
                elseif(substr($response['answer'], 0, 1) == '@')
                    return octdec(substr($response['answer'], 1));

                //otherwise, return the answer parsed as a octal number
                else
                    return octdec($response['answer']);
        }
    }

    /**
     * @return A new interpreter suitable for executing the provided initialization script.
     */
    private function create_interpreter($variables=null, $functions=null) {
        return qtype_scripted_language_manager::create_interpreter($this->language, $variables, $functions);
    }
    
    
    /**
     * Returns a sample correct answer, if we can determine one.
     */
    public function get_correct_response()
    {
        //if the question is a "must eval true" question, we can't easily determine the answer
        if($this->answer_mode == qtype_scripted_answer_mode::MODE_MUST_EVAL_TRUE) {
            return null;
        }

        //create a new interpreter
        $interpreter = $this->create_interpreter($this->vars, $this->funcs);
         
        //evaluate the correct answer to get a given reponse, if possible
        $answer_script = $this->get_correct_answer()->answer;
        $answer = array('answer' => $interpreter->evaluate($answer_script));

        //return the correct answer depending on the response mode
        switch($this->response_mode)
        {
            //if the answer is expected in binary, return the answer in binary
            case qtype_scripted_response_mode::MODE_BINARY:
                $answer['answer'] = decbin($answer['answer']);
                return $answer;
    
            //if the answer is expected in hex, return the answer in hex
            case qtype_scripted_response_mode::MODE_HEXADECIMAL:
                $answer['answer'] = dechex($answer['answer']);
                return $answer;

           //if the answer is expected in binary, return the answer in binary
            case qtype_scripted_response_mode::MODE_OCTAL:
                $answer['answer'] = decoct($answer['answer']);
                return $answer;

            //for any other type, return the answer directly
            default:
                return $answer;
        }
    }
    
    /**
     * Wrapper which specifies the method by which we want to serialzie script data.
     */
    static function safe_serialize($object) {
        return json_encode($object);
    }
    
    /**
    * Wrapper for unserialization; currently used in the event that we want to globally implement some safe serialization for
    * this question type.
    */
    static function safe_unserialize($string) {
        return json_decode($string, true);
    }

    /**
     * @deprecated Use handle_inline_code instead.
     */
    public function fill_in_variables($text, $interpreter) {
      //Pass the value to the newer handle-inline-code.
      return $this->handle_inline_code($text, $interpreter, 1, 'evaluate');
    }
}
