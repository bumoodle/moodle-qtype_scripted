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
 * Version information for the true/false question type.
 *
 * @package    qtype
 * @subpackage scripted
 * @copyright  2013 onwards Binghamton University
 * @author     Kyle J. Temkin <ktemkin@binghamton.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/engine/lib.php');
require_once($CFG->dirroot . '/question/type/scripted/question.php');
require_once($CFG->dirroot . '/question/type/shortanswer/questiontype.php');


/**
* Defines the scripted question type.
*
* @package    qtype
* @subpackage scripted
* @copyright  2011 Binghamton University
* @author	   Kyle Temkin <ktemkin@binghamton.edu>
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
class qtype_scripted extends qtype_shortanswer {

	/**
	 * Returns a list of the class fields which should be stored to the database.
	 */
    public function extra_question_fields() {
        return array('question_scripted', 'answers', 'init_code', 'answer_mode', 'response_mode', 'language');
    }

    /**
     * Get the possible score for a random guess.
     */
    public function get_random_guess_score($questiondata) {
      //We can't really estimate this.
      return null;
    }
}
