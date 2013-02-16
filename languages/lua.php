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

//Load each of the Looah sandboxed execution libraries.
foreach(array('Looah', 'LooahException', 'LineLimitException', 'StackOverflowException', 'LuaErrorException', 'TimeExceededException') as $library) {
    require_once($CFG->dirroot.'/question/type/scripted/lib/lua/'.$library.'.php');
}

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

/**
 *
 * @package    qtype
 * @subpackage scripted
 * @copyright  2013 onwards Binghamton University
 * @author     Kyle J. Temkin <ktemkin@binghamton.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_scripted_language_lua extends qtype_scripted_language {

    private $lua;

    private $environment = array();

    private $function_uids = array();

    /**
     * Creates a new MathScript adapter object.
     *
     * @param array $variables An optional array of varaibles which should be defined in the current execution environment, associating names to values.
     * @param array $functions An optional array of functions which should be defined in the current execution environment, associating names to content.
     * @param array $extensions_allowed An array listing the names of any extensions which should be loaded.
     */ 
    public function __construct($variables = null, $functions = null) {
  
        //TODO: Allow the path to the LUA interpreter to be specified.  
        $this->lua = new Looah\Looah();
  
        //And perform the common interpreter tasks. 
        parent::__construct($variables, $functions);
    }
  
    /**
     * @return The nicely-formatted name for the given programming language.
     */
    public function name() {
        return 'Lua';
    }
  
    /** 
     * Evaluates a block of Lua code.
     */ 
    public function execute($code) {
        $return = $this->lua->execute($code, $this->environment);
        return $return;
    }

    /**
     * Evaluates a Lua expression; does not permit side effects.
     */
    public function evaluate($expression) {

        //Wrap the code with a Lua wrapper that converts the results of the expression
        //to JSON. Note that this is instructor code, so only the instructor can "break"
        //their own questions.
        $code = self::wrap_with_json($expression);

        //Perform the computation, and extract the relevant object.
        $raw_result = $this->lua->execute($code, $this->environment);
        $result = json_decode($raw_result, true);

        //TODO: throw an exception on null?
        
        return $result[0];
    }

    /**
     * Wraps the given Lua code with the code required to construct a JSON object.
     */  
    private static function wrap_with_json($code) {
        //TODO: Throw an exception if an unclosed grouping operator is encountered.
        return 'print(json.encode({'.$code.'}))';
    }

    /**
     * Gets an associative array containing all known lua variables.
     * This is identical to the Lua environment, but without functions.
     */
    public function get_variables() {
        return $this->environment;
    }

  
    /**
     * Sets all variables in the current interpreter's environment.
     * @param array $values Associative array mapping name to value.
     */
    public function set_variables($values) {
        if($values) {
            $this->environment = $values;
        }
    }

    public function get_variable($name) {
        return null;
    }

    /**
     */
    private function & dereference_path($path) {

        

    }

  
    /**
     * Gets an associative array containing all given mathscript values.
     */
    public function get_functions() {
        return array();
    }
  
    /**
     * Sets all variables in the current interpreter's environment.
     * @param array $values Associative array mapping name to value.
     */
    public function set_functions($values) {
    }

    /**
     * Returns an array which contains a summary of all variables, in a format suitable
     * for printing. Used to list the variables which can be used in the question text.
     */
    public function summarize_variables() {
        $summary = array();
        $this->summarize_environment($this->environment, $summary);
        return $summary;
    }

    /**
     * A recursive function which summarizes a given environment, producing a single,
     * flat array mapping usable "variable" names to their values.
     * 
     * @param array $environment The environment to summarize, as an associative array.
     * @param &array $target The array to be filled.
     * @param string $path_prefix The path to the objet which is currently being summarized,
     *    as a lua expression. For example, if this function was being called on t[1], the path
     *    prefix would likely be "t[1]".
     */
    private function summarize_environment($environment, &$target, $path_prefix='') {

        //For each variable in the environment...
        foreach($environment as $name => $value) {

            //If this environment contains a set of Lua functions, create "stubs" which denote the 
            //presence of the given function.
            if($name == '_FUNCTIONS') {
                $this->stub_functions($target, $path_prefix, $value);
            } 
            //Otherwise, if we have an array, strip all functions from that array. 
            else if(is_array($value)) {
                $this->summarize_environment($value, $target, self::compute_path($name, $path_prefix));
           }
            else {
                $target[self::compute_path($name, $path_prefix)] = $value;
            }
        }
    }

    /**
     * Determines the "path" at which a given lua table element is locaetd, in a
     * concise printable form. For example, an with an index of 4 belonging
     * to a table named t.hello would be printed as t.hello[4], while an element
     * with an index of "snake" would be printed as t.hello.snake.
     * 
     * @param string $index The index of the given element.
     * @param string $table The name associated with the table that owns the given element.
     *    This can be a variable name, or a lua path, like a[3][4].
     */  
    private static function compute_path($index, $table) {

        //Base case: if we have an empty path prefix, pass the index
        //through unaltered, as it must be a valid Lua variable.
        if(empty($table)) {
            return $index;
        }

        //If we have an integer index, render it in array style.
        if(is_int($index)) {
            return $table . '[' . $index . ']';
        } 
        //If we have a index that contains lua-prohibited characters, 
        //present it in quoted-index style.
        else if(self::contains_special_characters($index)) {
            return $table . '["' . addcslashes($index, '"') . '"]';
        }
        //Otherwise, present the item in object style.
        else {
            return $table . '.' .$index;
        }
    }

    /**
     * Returns true iff the given function contains special characters
     * (and thus can't be used in lua's dot-prefixed object notation).
     */
    private static function contains_special_characters($string) {
        return (bool)preg_replace("/[A-Za-z0-9_]+/", '', $string);
    }

    /**
     * Creates short "function" stubs in the target array for each of the provided
     * instructions. Used to show functions
     */
    private function stub_functions(&$target, $path_prefix, $function_array, $stub_with='<function #?>') {
        //Create a simple function stub for each of the provided functions.
        foreach($function_array as $name => $function) {
            $stub = str_replace('?', $this->get_function_id($function), $stub_with);
            $target[self::compute_path($name, $path_prefix)] = $stub;
        }
    }


    /** 
     * Returns a short identifier which identifies each unique function body, for use in debugging.
     *
     * Any two functions with the same contents will be mapped to the same ID, allowing 
     * the identification of duplicate functions.
     */ 
    private function get_function_id($function_body) {

        //Determine if we've already given this function a unique ID. If we have, use it.  
        $unique_id = array_search($function_body, $this->function_uids);

        //Otherwise, assign the function a new ID, and add it to our array of known functions.
        if($unique_id === false) {
            
            //Use the first available unique ID.
            $unique_id = count($this->function_uids);

            //And add the function to our array of functions with known UIDs.
            $this->function_uids[$unique_id] = $function_body;
        }

        return $unique_id;

    }



    /**
     * Extracts information from a raised exception.
     * Returns an associative array of known error information.
     *
     * Most languages should override this function.
     */
    public function error_information($exception) {

      $message = $exception->getMessage();

      return array(
        'message'     =>  'Line '.$message,
        'line_number' =>  intval(current(explode(':', $message)))
      );
    }



}

class lua_path_parser {

    private $current_word  = "";

    private $path_components = array();

    private $parse_handler = 'parse_outer';


    /**
     * Parses the given path, and returns a list of the components that
     * make up the path. For example, 't[123].hat' would yield array('t', '123', 'hat').
     */
    public function parse($path) {

        //Parse each character of the path, passing each character to the appropriate
        //handler fro the given state.
        for($i = 0; $i < strlen($path); ++$i) {
            $this->{$this->parse_handler}($path[$i]);
        }

        //If we didn't make it back to a valid final state, we must have an unmateched item.
        if($this->parse_handler !== 'parse_outer' && $this->parse_handler !== 'parse_post_close') {
            throw new InvalidArgumentException('Unexpected end of line.');
        }

        //If we have a word on the stack, pop it.
        if($this->current_word !== ''){
            $this->push_word_and_move_to('parse_outer');
        }

        //And return the created path components.
        return $this->path_components;
    }

    /**
     * Handle parsing for the cases in which we're outside of any operators.
     */ 
    private function parse_outer($character) {

        switch($character) {

            //If we've hit a "object property"-style dot,
            //push the current word, and remain in the outer state.
            case '.':
                $this->push_word_and_move_to('parse_outer');
                return;
                
            //If we've hit the start of an indexing operator,
            //move to the indexing state.
            case '[':    
                $this->push_word_and_move_to('parse_indexing');
                return;
            
            default:
                $this->push_character($character, false);
                return;
        }
    }

    /**
     * Handle parsing for the case in which we're inside of an indexing operator, 
     * but not inside of a quoted string. 
     */
    private function parse_indexing($character) {

        switch($character) {

            //Handle a double-quote; which is either an open-quotation
            //or an invalid character.
            case '"':

                //If the current word isn't empty, this quote was unexpected.
                if($this->current_word !== '') {
                    throw new InvalidArgumentException('Unexpected double quote inside of an indexing operator.');
                }

                //Switched to the "in-quote" state.
                $this->parse_handler = 'parse_quoted';
                return;


            //Handles the end of the indexing operator.
            case ']':

                //Push the given word to the stack, and move to the outer state.
                $this->parse_handler = 'parse_post_close';
                return;

            //If we have a whitespace character, ignore it.
            case ' ':
            case "\t":
                //If we don't have an empty word, this _must_ be trailing whitespace,
                //move to the "expecting close operator" state.
                if($this->current_word !== '') {
                    $this->parse_handler = 'parse_expecting_close_index';
                }

                return;

            //If we have any other character, push it directly into the 
            //current word.
            default:
                $this->push_character($character, false);
                return;
        
        }

    }

    private function parse_quoted($character) {

        switch($character)  {

            //If we have an escape character, move to the "escape" state.
            case '\\':
                $this->parse_handler = 'parse_quoted_escape';
                return;

            //If we have a close-quote, move to the "expecting close indexing" state,
            //as the only valid next symbol is the "]" close operator (or whitespace).
            case '"':
                $this->parse_handler = 'parse_expecting_close_index';
                return;

            //Otherwise, push the character into the current word
            //without performing any validation.
            default:
                $this->push_character($character, true);
                return;
        
        }
    
    }

    private function parse_quoted_escape($character) {
        //Push the character directly to the current word,
        //no matter what it is.
        $this->push_character($character, true);

        //And move back to the in-quotes state.
        $this->parse_handler = 'parse_quoted';
    }

    private function parse_expecting_close_index($character) {

        switch($character) {

            //If we have a space or tab, remain in the same state. 
            case ' ':
            case "\t":
                return;

            //If we have a close indexing operator, move to the
            //post-close state, where we expect either another
            //indexing operator, or the object notation '.' 
            case ']':
                $this->parse_handler = 'parse_post_close';
                return;

            //Otherwise, we have an invalid character.
            default:
                throw new InvalidArgumentException('Unexpected character after double quote, expecting "]".');
        }
    
    }

    private function parse_post_close($character) {
    
        switch($character) {

            //Skip any whitespace characters we encounter,
            //as this is lua's behavior.
            case ' ':
            case "\t":
                return;

            //If we've encountered an open indexing operator,
            //push the current word and move to the indexing state.
            case '[':
                $this->push_word_and_move_to('parse_indexing');
                return;

            //If we've encounterd an object property '.',
            //push the current word and move to the outer state.
            case '.':
                $this->push_word_and_move_to('parse_outer');
                return;

            default:
                throw new InvalidArgumentException('Unexpected character after indexing operator ("[]"), expecting "[" or ".".');
        }
    
    }

    /**
     * Pushes the given character onto the current word.
     *
     * @param string $character The character to push.
     * @param bool $quoted If true, the given character is outside of a quote, and expected to be alphanumeric.
     */
    private function push_character($character, $quoted=false) {

        //If we have an invalid variable character, raise an argument error.
        if(!$quoted && preg_match('/[^A-Za-z0-9_]/', $character)) {
            throw new InvalidArgumentException('Invalid character received ('.$character.')  outside of a quoted expression.');
        }

        //Add the character to the current word, and continue.
        $this->current_word .= $character;
    
    }

    /**
     * Pushes the curent word onto the parsed path, and starts a new word.
     */
    private function push_word_and_move_to($next_state = null) {

        //If we have an empty word, raise an error.
        if($this->current_word === '') {
            throw new InvalidArgumentException('Received an empty index as a path component!');
        }

        $this->path_components[] = $this->current_word;
        $this->current_word = '';

        //If we have a next-state specified, apply it.
        if($next_state !== null) {
            $this->parse_handler = $next_state;
        }
    }
}
