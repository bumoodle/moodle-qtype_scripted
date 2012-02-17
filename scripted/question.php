<?php

defined('MOODLE_INTERNAL') || die();

require_once 'evalmath.class.php';

class qtype_scripted_response_mode
{
    /**
     *  Interpret the user's response as a string, which may be numeric, if MODE_MUST_EQUAL is selected.
     */
    const MODE_STRING = 0;

    /**
     * Interpret the user's response as a floating point number, which is base ten.
     */
    const MODE_NUMERIC = 1;

    /**
     * Interpret the user's response as a binary number, which is converted to an integer before checking. 
     */
    const MODE_BINARY =  2;

    /**
     * Interpret the user's response as a hexadecimal number, which is converted to an integer before checking.
     */
    const MODE_HEXADECIMAL = 3;


    /**
     * Interpret the user's response as an octal number, which is converted to an integer before checking.
     */
    const MODE_OCTAL = 4;
}

class qtype_scripted_answer_mode
{
	/**
	 * True to indciate that a correct response must be equal (using PHP's ==) to the evaluated answer. 
	 * 
	 * @var int
	 */
	const MODE_MUST_EQUAL = 0;
	
	/**
	* True to indciate that a correct response must be congruent (using PHP's ===) to the evaluated answer.
	*
	* @var int
	*/
	const MODE_MUST_BE_CONGRUENT = 1;
	
	/**
	 * True to indicate that the evaluated answer (which will depend on the user's response) must be nonzero.
	 * 
	 * @var int
	 */
	const MODE_MUST_EVAL_TRUE = 2;
}

/**
* Defines the editing form for the scripted question type.
*
* @package    qtype
* @subpackage scripted
* @copyright  2011 Binghamton University
* @author	  Kyle Temkin <ktemkin@binghamton.edu>
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
class qtype_scripted_question extends question_graded_by_strategy implements question_response_answer_comparer 
{
    /** @var int (member of answer_mode) Indicates the grading mechanism used to compare answers . */
    public $answer_mode;
    
    /** @var string A short block of EvalScript to be executed upon the instantiation of a new question. */
    public $init_code;
    
    /** @var int (member of qtype_scripted_response_mode)   A short identifer which determines how this question will be graded. */
    public $reponse_mode;
    
    /** @var array of question_answer. */
    public $answers = array();
    
    /** @var array - an array containing the variables created by the initialization script */
    private $vars;
    
    /** @var array - an array containing the functions defined by the initialization script */
    private $funcs;

    /**
     * Creates a new Scripted question instance.
     */
    public function __construct() 
    {
        parent::__construct(new question_first_matching_answer_grading_strategy($this));
    }

    /**
     * Indicates the type of answer data which Moodle expects to recieve; the scripted quesiton type
     * always recieves the same, single answer.
     */
    public function get_expected_data() 
    {
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
    public function start_attempt(question_attempt_step $step, $variant) 
    {
    	//no response, by default
    	//$state->responses = array('' => '');
    	
    	//create a EvalMath mathematics evaulator
    	$m = new EvalMath();
    	$m->suppress_errors = true;
    	
    	//find all variables in the question text, and initialize them to 0
    	$variables = self::find_all_variables($this->questiontext);
    	
    	//initialize each variable to zero in case they aren't overridden in the init script
    	foreach($variables as $var)
    		$m->evaluate($var .'=0');
    	
    	//run the initlization "script"
    	$errors = $m->evaluate_script($this->init_code);
    	
    	//TODO: handle errors
    	
    	//store the list of variables after the execution, for storage in the database
    	$step->set_qt_var('_vars', self::safe_serialize($m->vars()));
    	$step->set_qt_var('_funs', self::safe_serialize($m->funcs_raw()));
    	
    	//store a local copy of the EvalMath state
    	$this->vars = $m->vars();
    	$this->funcs = $m->funcs_raw();
    }
    
    /**
     * (non-PHPdoc)
     * @see question_definition::apply_attempt_state()
     */
    public function apply_attempt_state(question_attempt_step $step)
    {
    	$this->vars = self::safe_unserialize($step->get_qt_var('_vars'));
    	$this->funcs = self::safe_unserialize($step->get_qt_var('_funcs'));
    }
    
    
    /**
     * Summarizes a student response for the review (e.g. the Review Attempt view). 
     */
    public function summarise_response(array $response) 
    {
    	//if a reponse has been provided, 
        if (isset($response['answer'])) 
            return $response['answer'];
        else 
            return null;
    }

    /***
     * Returns true if the given response is considered valid or complete.
     */
    public function is_complete_response(array $response) 
    {
        //determine gradability based on response type
        switch($this->response_mode)
        {
            //in string mode, accept any non-empty string
            case qtype_scripted_response_mode::MODE_STRING:
                return $response['answer'] !== "";

            //in numeric mode, accept any numeric string
            case qtype_scripted_response_mode::MODE_NUMERIC:
                return is_numeric($response['answer']);

            //in binary mode, check to see if the number is valid binary using a regex
            case qtype_scripted_response_mode::MODE_BINARY:
                return preg_match('#^(0b|\%)?[01]+$#', $response['answer']) === 1;

            //do the same for hexadecimal
            case qtype_scripted_response_mode::MODE_HEXADECIMAL:
                return preg_match('#^(0x|\$)?[0-9a-fA-F]+$#', $response['answer']) === 1;

            //do the same for octal 
            case qtype_scripted_response_mode::MODE_OCTAL:
                return preg_match('#^(0o|\@)?[0-7]+$#', $response['answer']) === 1;



        }

    	//return true iff a non-null response has been given
        //return array_key_exists('answer', $response) && ($response['answer'] || $response['answer'] === '0');
    }

//    /**
//     * Returns true iff the given string is composed only of valid characters. 
//     *
//     * An optional set of prefixes may be specified; if they are, a string may begin with one of the prefixes despite the other rules.
//     */
//    protected static function is_composed_of($string, $valid_chars, $valid_prefixes = array(), $allow_multiple_prefixes = false)
//    {
//        //for each of the valid prefixes
//        for($valid_prefixes as $prefix)
//        {
//            //if the target string starts with that prefix, remove it
//            if(substr($string, 0, strlen($prefix)) == $prefix)
//            {
//                //remove the prefix
//                $string = substr($string, strlen($prefix));
//
//                //if we don't want to allow multiple prefixes, skip the rest of the list
//                if(!$allow_multiple_prefixes)
//                    break;
//            }
//        }
//
//    
//
//
//    }
//

    /***
     * Returns an error message, in case the response won't validate. 
     */
    public function get_validation_error(array $response) 
    {
        //if the string was gradeable, don't indicate an error
        if ($this->is_gradable_response($response)) 
            return '';

        //otherwise, indicate an error depending on the type of reponse which was expected
        switch($this->response_mode)
        {
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
    public function is_same_response(array $prevresponse, array $newresponse) 
    {
        return question_utils::arrays_same_at_key_missing_is_blank($prevresponse, $newresponse, 'answer');
    }

    /**
     * Returns all possible answers (including distractors) for the given question.
     */
    public function get_answers() 
    {
        return $this->answers;
    }

    /**
     * Compares a given response with a given answer; the way which this is performed is determined by the
     * answer_mode variable.
     * 
     * TODO TODO TODO Implement.
     */
    public function compare_response_with_answer(array $response, question_answer $answer) 
    {  	

    	//parse the response according to the selected response mode
    	$value = $this->parse_response($response);
    
    	//create a new math evaluation object
    	$m = new EvalMath();
    	$m->suppress_errors = true;
    	
    	//define all known functions and variables (defined in the init script)
    	$m->vars($this->vars);
    	$m->funcs_raw($this->funcs);
    	
    	
    	switch($this->answer_mode)
    	{
    		//for direct/answer modes,
    		case qtype_scripted_answer_mode::MODE_MUST_EQUAL:
    	
    			//evaluate the given answer formula
    			$ans = $m->evaluate($answer->answer);
    	
    			//the response is correct iff it evaluates to the same value as the answer:
    	
    			//if the two are both numeric, compare them loosely, without regard to type; so 5 == "05" is true
    			if(is_numeric($ans) && is_numeric($value))
    			{
    				return $ans == $value;
    			}
    			//otherwise, compare them stricly; so "5a" !== 5; (note that, had we performed a loose compare, "5a" == 5 is true due to type juggling >.<)
    			else
    			{
    				return $ans === $value;
    			}
    	
    			//for boolean modes,
    			 
    			    			
    			//case 'boolean':
    		case qtype_scripted_answer_mode::MODE_MUST_EVAL_TRUE:
    	
                //we define the answer variable "resp"
    			$m->evaluate('resp='.$value);
    	
    			//and return true iff the answer evaluates to True
    			return (bool)$m->evaluate($answer->answer);
    	
    		default:
    	
    			//something's gone wrong
    			throw new coding_exception('Invalid grading mode for calculated sane qtype.');
    	
    	}
    }

    public static function compare_string_with_wildcard($string, $pattern, $ignorecase) 
    {
        // Break the string on non-escaped asterisks.
        $bits = preg_split('/(?<!\\\\)\*/', $pattern);
        // Escape regexp special characters in the bits.
        $excapedbits = array();
        foreach ($bits as $bit) {
            $excapedbits[] = preg_quote(str_replace('\*', '*', $bit));
        }
        // Put it back together to make the regexp.
        $regexp = '|^' . implode('.*', $excapedbits) . '$|u';

        // Make the match insensitive if requested to.
        if ($ignorecase) {
            $regexp .= 'i';
        }

        return preg_match($regexp, trim($string));
    }

    /**
     * Determines which question-related files the user is allowed to access.
     * 
     * In our case, the user should be able to view the question, and its hints.
     */
    public function check_file_access($qa, $options, $component, $filearea, $args, $forcedownload) 
    {
        if ($component == 'question' && $filearea == 'answerfeedback') 
        {
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
     * 
     */
	public function format_questiontext(question_attempt $qa)
	{
		//get a list of varaibles created by the initialization MathScript 
		$vars = self::safe_unserialize($qa->get_last_qt_var('_vars'));
		
		//get the quesiton text, with all known variables replaced with their values
		$questiontext = self::replace_variables($this->questiontext, $vars);
		
		//run the question text through the basic moodle formatting engine
		return $this->format_text($questiontext, $this->questiontextformat, $qa, 'question', 'questiontext', $this->id);
		
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
                return $response['answer'];

            //handle DECIMAL-mode responses
            case qtype_scripted_response_mode::MODE_NUMERIC:

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
    
	
	public function get_correct_response()
	{
		//create a new math evaluation object:
		$m = new EvalMath();
		$m->suppress_errors = true;
		 
		//define all known functions and variables (defined in the init script)
		$m->vars($this->vars);
		$m->funcs_raw($this->funcs);
		
		//evaluate the correct answer to get a given reponse, if possible
		return array('answer' => $m->evaluate($this->get_correct_answer()->answer));
		
		//TODO: in the boolean mode, don't provide a correct answer (boolean satisfiability problem)
	}
	
	/**
	* Generate a brief, plain-text, summary of the correct answer to this question.
	* This is used by various reports, and can also be useful when testing.
	* This method will return null if such a summary is not possible, or
	* inappropriate.
	* @return string|null a plain text summary of the right answer to this question.
	*/
	/*
	public function get_right_answer_summary()
	{
		return "Test summary.";
	}
	*/
	
	
	/**
	 * Wrapper for serialization; currently used in the event that we want to globally implement some safe serialization for
	 * this question type.
	 * 
	 * FIXME FIXME FIXME implement safety!
	 */
	static function safe_serialize($object)
	{
		//TODO
		
		return serialize($object);
	}
	
	/**
	* Wrapper for unserialization; currently used in the event that we want to globally implement some safe serialization for
	* this question type.
	*
	* FIXME FIXME FIXME implement safety!
	*/
	static function safe_unserialize($string)
	{
		return unserialize($string);
	}
	
	
	/**
	* Replaces all variables surrounded with curly braces in a block of text with their values.
	*
	* @param string $text 	The block of text which contains varaibles for substitution.
	* @param array $vars	The full list of variables generated after the initialization MathScript.
	* @return string		The provided text, with all known variable names replaced with their values.
	*/
	static function replace_variables($text, array $vars)
	{
		//replace each variable in the text with its value
		foreach($vars as $name => $value)
		$text = str_replace('{'.$name.'}', $value, $text);
	
		return $text;
	}
	
    /**
     * Locates all variable-formatted entries in the question text.
     * MathScript variables are formatted as {varname}, where varname is the variable name.
     * 
     * @param string $text The HTML question text.
     * @return An array of variable names.
     */
    static function find_all_variables($text)
    {
    	//extract all items of the form {[A-Za-z]+}, which are our variables
    	$variables = preg_match_all("|\{([A-Za-z])+\}|", $text, $matches, PREG_SET_ORDER);
    
    	//return the first element of each match- the variable name without the curly braces
    	return array_map(function($arr) { return $arr[1]; },  $matches);
    
    }
    
    
}
