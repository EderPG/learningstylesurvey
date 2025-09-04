<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_learningstylesurvey_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Versión 2025070401 - Tablas básicas de archivos y evaluaciones
    if ($oldversion < 2025070401) {
        // Tabla de archivos por ruta.
        $table1 = new xmldb_table('learningstylesurvey_path_files');
        if (!$dbman->table_exists($table1)) {
            $table1->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table1->add_field('pathid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table1->add_field('filename', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table1->add_field('steporder', XMLDB_TYPE_INTEGER, '10', null, false, null, '0');
            $table1->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table1->add_key('path_fk', XMLDB_KEY_FOREIGN, ['pathid'], 'learningstylesurvey_paths', ['id']);
            $dbman->create_table($table1);
        }

        // Tabla de evaluaciones por ruta.
        $table2 = new xmldb_table('learningstylesurvey_path_evaluations');
        if (!$dbman->table_exists($table2)) {
            $table2->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table2->add_field('pathid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table2->add_field('quizid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table2->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table2->add_key('path_fk', XMLDB_KEY_FOREIGN, ['pathid'], 'learningstylesurvey_paths', ['id']);
            $dbman->create_table($table2);
        }

        upgrade_mod_savepoint(true, 2025070401, 'learningstylesurvey');
    }

    // Versión 2025090401 - REPARACIÓN COMPLETA DE CAMPOS FALTANTES
    if ($oldversion < 2025090401) {
        
        // 1. ASEGURAR que table learningstylesurvey_resources tenga campo userid
        $resourcestable = new xmldb_table('learningstylesurvey_resources');
        if ($dbman->table_exists($resourcestable)) {
            $useridfield = new xmldb_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            if (!$dbman->field_exists($resourcestable, $useridfield)) {
                $dbman->add_field($resourcestable, $useridfield);
            }
            
            $timecreatedfield = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            if (!$dbman->field_exists($resourcestable, $timecreatedfield)) {
                $dbman->add_field($resourcestable, $timecreatedfield);
            }
        }

        // 2. ASEGURAR que tabla learningstylesurvey_temas tenga campo userid 
        $temastable = new xmldb_table('learningstylesurvey_temas');
        if ($dbman->table_exists($temastable)) {
            $useridfield = new xmldb_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            if (!$dbman->field_exists($temastable, $useridfield)) {
                $dbman->add_field($temastable, $useridfield);
            }
        }

        // 3. ASEGURAR que tabla learningstylesurvey_paths tenga campo cmid
        $pathstable = new xmldb_table('learningstylesurvey_paths');
        if ($dbman->table_exists($pathstable)) {
            $cmidfield = new xmldb_field('cmid', XMLDB_TYPE_INTEGER, '10', null, false, null, null);
            if (!$dbman->field_exists($pathstable, $cmidfield)) {
                $dbman->add_field($pathstable, $cmidfield);
            }
        }

        // 4. ASEGURAR que tabla learningstylesurvey_quiz_results tenga campo timemodified
        $resultstable = new xmldb_table('learningstylesurvey_quiz_results');
        if ($dbman->table_exists($resultstable)) {
            $timemodifiedfield = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, false, null, '0');
            if (!$dbman->field_exists($resultstable, $timemodifiedfield)) {
                $dbman->add_field($resultstable, $timemodifiedfield);
            }
        }

        // 5. ASEGURAR que tabla learningpath_steps tenga campos de redirección
        $stepstable = new xmldb_table('learningpath_steps');
        if ($dbman->table_exists($stepstable)) {
            $passfield = new xmldb_field('passredirect', XMLDB_TYPE_INTEGER, '10', null, false, null, null);
            if (!$dbman->field_exists($stepstable, $passfield)) {
                $dbman->add_field($stepstable, $passfield);
            }
            
            $failfield = new xmldb_field('failredirect', XMLDB_TYPE_INTEGER, '10', null, false, null, null);
            if (!$dbman->field_exists($stepstable, $failfield)) {
                $dbman->add_field($stepstable, $failfield);
            }
            
            $istest = new xmldb_field('istest', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            if (!$dbman->field_exists($stepstable, $istest)) {
                $dbman->add_field($stepstable, $istest);
            }
        }

        upgrade_mod_savepoint(true, 2025090401, 'learningstylesurvey');
    }

    return true;
}
