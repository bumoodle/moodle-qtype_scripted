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

//Bootstrap code which registers known languages is provided at the end of this file.

/**
 * Static class which manages the languages available to the scripted
 * language type.
 *
 * @package    qtype
 * @subpackage scripted
 * @class qtype_scripted_language_manager
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

    //If we weren't provided with a language, assume MathScript, for legacy compatibility.
    //TODO: Write update code and remove this!
    if(empty($language)) {
      $language = 'mathscript';
    }

    //If we weren't able to find the given language, raise an exception.
    if(!in_array($language, self::get_available_languages())) {
      throw new coding_exception(get_string('invalidlanguage', 'qtype_scripted'), get_string('invalidlanguage_debug', 'qtype_scripted', $language));
    }

    //Create a new interpreter, and return it.
    $class = 'qtype_scripted_language_'.$language;
    return new $class($variables, $functions);
  }

}

/** Base class for all scripting language exceptions. */
class qtype_scripted_language_exception extends exception 
{
    public function shortMessage() {
      return $this->message;
    }
}

/**
 * Generic exception which can be used to wrap errors raised by
 * scripting langauges that do not extend qtype_scripted_language_exception.
 */ 
class qtype_scripted_language_interpreter_exception extends qtype_scripted_language_exception {

    public function __construct($wrapped_exception) {
        $this->inner_exception = $wrapped_exception; 
        parent::__construct($this->inner_exception->getMessage());
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
    $vars = $this->get_variables();
    $vars[$name] = $this->preprocess_value($value);
    $vars = $this->set_variables($vars);
  }

  /**
   * convenience shortcut for set_variable().
   */
  public function __set($variable, $value) {
    return set_variable($variable, $value);
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


}

//Require all of the local language plugins.
foreach(glob(dirname(__FILE__)."/languages/*.php") as $file) {

  //Include the source file that contains the class definition...
  include_once($file);

  //And add the language to our list of known languages.
  qtype_scripted_language_manager::register_language(basename($file, '.php'));

}






