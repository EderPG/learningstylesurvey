<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_learningstylesurvey_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025070401) {

        // Tabla de archivos por ruta.
        $table1 = new xmldb_table('learningstylesurvey_path_files');
        $table1->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table1->add_field('pathid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table1->add_field('filename', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table1->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table1->add_key('path_fk', XMLDB_KEY_FOREIGN, ['pathid'], 'learningstylesurvey_paths', ['id']);

        if (!$dbman->table_exists($table1)) {
            $dbman->create_table($table1);
        }

        // Tabla de evaluaciones por ruta.
        $table2 = new xmldb_table('learningstylesurvey_path_evaluations');
        $table2->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table2->add_field('pathid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table2->add_field('quizid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table2->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table2->add_key('path_fk', XMLDB_KEY_FOREIGN, ['pathid'], 'learningstylesurvey_paths', ['id']);

        if (!$dbman->table_exists($table2)) {
            $dbman->create_table($table2);
        }

        upgrade_mod_savepoint(true, 2025070401, 'learningstylesurvey');
    }

    return true;
}
