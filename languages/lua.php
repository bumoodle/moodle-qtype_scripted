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

        //Evaluate the given block of code.
        try {
            return $this->lua->execute($code, $this->environment);
        } 
        //If an error occurs, wrap the existing exception with a scripted_language exception.
        catch(looah\LooahException $e) {
            throw new qtype_scripted_language_interpreter_exception($e);
        }
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
        $raw_result = $this->execute($code, $this->environment);
        $result = json_decode($raw_result, true);

        //If we weren't able to decode lua's response...
        if(!$result || !array_key_exists(0, $result)) {
          throw new qtype_scripted_invalid_result('Could not evaluate:'.$expression);
        }

        //Otherwise
        return $result[0];
    }

  
    private function wrap_with_json($code) {
        $this->validate_expression($code); 
        return 'print(json.encode({'.$code.'}))';
    }

    /**
     * Verifies that a given expression can be used with wrap_with_json.
     * If the expression is invalid, raises an exception.
     */ 
    private function validate_expression($expression) {
        //Ensure that the expression is assignable.
        //This prevents an ugly error message for invalid expressions.
        $this->execute('result ='.$expression);
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

    /**
     * Returns an array which contains a summary of all variables, in a format suitable
     * for printing. Used to list the variables which can be used in the question text.
     */
    public function summarize_variables() {
        $summary = array();
        self::summarize_environment($this->environment, $summary);
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
    private static function summarize_environment($environment, &$target, $path_prefix='') {

        //For each variable in the environment...
        foreach($environment as $name => $value) {

            //If this environment contains a set of Lua functions, create "stubs" which denote the 
            //presence of the given function.
            if($name == '_FUNCTIONS' && is_array($value)) {
                self::stub_functions($target, $path_prefix, $value);
            } 
            //Otherwise, if we have an array, strip all functions from that array. 
            else if(is_array($value)) {
                self::summarize_environment($value, $target, self::compute_path($name, $path_prefix));
            }
            else {
                $target[self::compute_path($name, $path_prefix)] = var_export($value, true);
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
    private static function stub_functions(&$target, $path_prefix, $function_array, $stub_with='<function #? >') {
        
        //Create a simple function stub for each of the provided functions.
        foreach($function_array as $name => $function) {
            $stub = str_replace('?', self::get_unique_function_number($function), $stub_with);
            $target[self::compute_path($name, $path_prefix)] = $stub;
        }
    }

    /**
     * Assign each function a unique ID according to its contents.
     * This allows each function to be uniquely identified with an expression that's 
     * shorter than a hash.
     */
    private static function get_unique_function_number($function) {
    
        static $functions = array();

        //If this function has already been assigned a number, find it.
        $uid = array_search($function, $functions);

        //Otherwise, assign it a new, unique number.
        if($uid === false) {
            $uid = count($functions);
            $functions[] = $function; 
        }

        //Return the function's unique ID.
        return $uid;
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
