<?php

/***
 * Automatically-generated Database migration script.
 */ 
function xmldb_qtype_scripted_upgrade($oldversion = 0) {
    global $DB;
    $dbman = $DB->get_manager();

    
    if ($oldversion < 2013020600) {

      // Define field language to be added to question_scripted
      $table = new xmldb_table('question_scripted');
      $field = new xmldb_field('language', XMLDB_TYPE_TEXT, null, null, null, null, null, 'response_mode');

      // Conditionally launch add field language
      if (!$dbman->field_exists($table, $field)) {
          $dbman->add_field($table, $field);
      }

      // match savepoint reached
      upgrade_plugin_savepoint(true, 2013020600, 'qtype', 'scripted');

    }
 

}
