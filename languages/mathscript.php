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

//DEPRECATED MathScript libraries.
foreach(array('.class', '_randomization', '_binary', '_control', '_legacy', '_debug', '_string', '_logic', '_array') as $library) {
    require_once($CFG->dirroot.'/question/type/scripted/lib/mathscript/mathscript'.$library.'.php');
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
/**
 * Adapter which adjusts the legacy MathScript engine to use provide
 * a common interface with the modern (common) scripting languages.
 *
 * @package    qtype
 * @subpackage scripted
 * @copyright  2013 onwards Binghamton University
 * @author     Kyle J. Temkin <ktemkin@binghamton.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_scripted_language_mathscript extends qtype_scripted_language {

  /**
   * A list of MathScript extensions allowed.
   * @deprecated
   */
  static $extensions_allowed = array('spreadsheet', 'basicmath', 'randomization', 'binary', 'control', 'legacy', 'debug', 'string', 'logic', 'array');

  private $mathscript;

  /**
   * Creates a new MathScript adapter object.
   *
   * @param array $variables An optional array of varaibles which should be defined in the current execution environment, associating names to values.
   * @param array $functions An optional array of functions which should be defined in the current execution environment, associating names to content.
   * @param array $extensions_allowed An array listing the names of any extensions which should be loaded.
   */ 
  public function __construct($variables = null, $functions = null, $extensions_allowed = array('spreadsheet', 'basicmath')) {

    //Create the wrapped MathScript instance...
    $this->mathscript = new MathScript($extensions_allowed);
    $this->mathscript->suppress_errors = true;

    //And set its properties.
    $this->set_variables($variables); 
    $this->set_functions($functions); 
  }

  /**
   * @return The nicely-formatted name for the given programming language.
   */
  public function name() {
    return 'MathScript';
  }

  /** 
   * Evaluates a block of MathScript code.
   */ 
  public function execute($code) {
    return $this->mathscript->evaluate_script($code);
  }

  /** 
   * Evaluates a block of MathScript code.
   */ 
  public function evaluate($code) {
    return $this->mathscript->evaluate($code);
  }


  /**
   * Gets an associative array containing all given mathscript values.
   */
  public function get_variables() {
    return $this->mathscript->vars();
  }

  /**
   * Sets all variables in the current interpreter's environment.
   * @param array $values Associative array mapping name to value.
   */
  public function set_variables($values) {
    return $this->mathscript->vars($values);
  }

  /**
   * Gets an associative array containing all given mathscript values.
   */
  public function get_functions() {
    return $this->mathscript->funcs_raw();
  }

  /**
   * Sets all variables in the current interpreter's environment.
   * @param array $values Associative array mapping name to value.
   */
  public function set_functions($values) {
    return $this->mathscript->funcs_raw($value);
  }

}
