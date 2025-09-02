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

    $newversion = 2025082203;
    if ($oldversion < $newversion) {
        // Crear tabla de relación entre rutas y temas si no existe
        $table = new xmldb_table('learningstylesurvey_path_temas');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('pathid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('temaid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('orden', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('isrefuerzo', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
            $table->add_key('path_fk', XMLDB_KEY_FOREIGN, array('pathid'), 'learningstylesurvey_paths', array('id'));
            $table->add_key('tema_fk', XMLDB_KEY_FOREIGN, array('temaid'), 'learningstylesurvey_temas', array('id'));
            $dbman->create_table($table);
        }
        
        // Agregar campo timemodified a la tabla de resultados de quiz si no existe
        $resultstable = new xmldb_table('learningstylesurvey_quiz_results');
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, false, null, '0');
        if (!$dbman->field_exists($resultstable, $field)) {
            $dbman->add_field($resultstable, $field);
        }
        
        upgrade_mod_savepoint(true, $newversion, 'learningstylesurvey');
    }

    // Versión 2025082205 - Crear estructuras de rutas adaptativas que faltaron
    $newversion2 = 2025082205;
    if ($oldversion < $newversion2) {
        // Crear tabla de relación entre rutas y temas si no existe
        $table = new xmldb_table('learningstylesurvey_path_temas');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('pathid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('temaid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('orden', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('isrefuerzo', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
            $table->add_key('path_fk', XMLDB_KEY_FOREIGN, array('pathid'), 'learningstylesurvey_paths', array('id'));
            $table->add_key('tema_fk', XMLDB_KEY_FOREIGN, array('temaid'), 'learningstylesurvey_temas', array('id'));
            $dbman->create_table($table);
        }
        
        // Agregar campo timemodified a la tabla de resultados de quiz si no existe
        $resultstable = new xmldb_table('learningstylesurvey_quiz_results');
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, false, null, '0');
        if (!$dbman->field_exists($resultstable, $field)) {
            $dbman->add_field($resultstable, $field);
        }
        
        upgrade_mod_savepoint(true, $newversion2, 'learningstylesurvey');
    }

    return true;
}
