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

  
    private static function wrap_with_json($code) {
        //TODO: Throw an exception if an unclosed grouping operator is encountered.
        return 'print(json.encode({'.$code.'}))';
    }

    /**
     * Gets an associative array containing all given mathscript values.
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

}
