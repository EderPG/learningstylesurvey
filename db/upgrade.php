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

    // Versión 2025082206 - FORZAR creación de estructuras faltantes
    $newversion3 = 2025082206;
    if ($oldversion < $newversion3) {
        
        // FORZAR creación de tabla path_temas aunque ya exista
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
        
        // FORZAR agregar campo timemodified a quiz_results
        $resultstable = new xmldb_table('learningstylesurvey_quiz_results');
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, false, null, '0');
        if (!$dbman->field_exists($resultstable, $field)) {
            $dbman->add_field($resultstable, $field);
        }
        
        // VERIFICAR que la tabla de recursos tenga el campo tema
        $resourcestable = new xmldb_table('learningstylesurvey_resources');
        $temafield = new xmldb_field('tema', XMLDB_TYPE_INTEGER, '10', null, false, null, null);
        if (!$dbman->field_exists($resourcestable, $temafield)) {
            $dbman->add_field($resourcestable, $temafield);
        }
        
        // VERIFICAR que learningpath_steps tenga los campos de salto
        $stepstable = new xmldb_table('learningpath_steps');
        $passfield = new xmldb_field('passredirect', XMLDB_TYPE_INTEGER, '10', null, false, null, null);
        if (!$dbman->field_exists($stepstable, $passfield)) {
            $dbman->add_field($stepstable, $passfield);
        }
        
        $failfield = new xmldb_field('failredirect', XMLDB_TYPE_INTEGER, '10', null, false, null, null);
        if (!$dbman->field_exists($stepstable, $failfield)) {
            $dbman->add_field($stepstable, $failfield);
        }
        
        upgrade_mod_savepoint(true, $newversion3, 'learningstylesurvey');
    }

    // Versión 2025082207 - Agregar campo userid a resources para filtrar por maestro
    $newversion4 = 2025082207;
    if ($oldversion < $newversion4) {
        
        // Agregar campo userid a la tabla de recursos
        $resourcestable = new xmldb_table('learningstylesurvey_resources');
        $useridfield = new xmldb_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        
        if (!$dbman->field_exists($resourcestable, $useridfield)) {
            $dbman->add_field($resourcestable, $useridfield);
        }
        
        // Agregar campo timecreated a resources si no existe
        $timecreatedfield = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($resourcestable, $timecreatedfield)) {
            $dbman->add_field($resourcestable, $timecreatedfield);
        }
        
        // Agregar campo userid a quizzes si no existe
        $quizzestable = new xmldb_table('learningstylesurvey_quizzes');
        $quizuseridfield = new xmldb_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($quizzestable, $quizuseridfield)) {
            // El campo ya existe según install.xml, solo verificamos
        }
        
        upgrade_mod_savepoint(true, $newversion4, 'learningstylesurvey');
    }

    // Versión 2025082208 - Agregar campo userid a temas para filtrado por usuario
    $newversion5 = 2025082208;
    if ($oldversion < $newversion5) {
        $temastable = new xmldb_table('learningstylesurvey_temas');
        $useridfield = new xmldb_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        
        if (!$dbman->field_exists($temastable, $useridfield)) {
            $dbman->add_field($temastable, $useridfield);
        }
        
        upgrade_mod_savepoint(true, $newversion5, 'learningstylesurvey');
    }

    // Version 2025090402: Agregar campo cmid a la tabla de rutas
    $newversion6 = 2025090402;
    if ($oldversion < $newversion6) {
        $pathstable = new xmldb_table('learningstylesurvey_paths');
        $cmidfield = new xmldb_field('cmid', XMLDB_TYPE_INTEGER, '10', null, false, null, null);
        
        if (!$dbman->field_exists($pathstable, $cmidfield)) {
            $dbman->add_field($pathstable, $cmidfield);
        }
        
        upgrade_mod_savepoint(true, $newversion6, 'learningstylesurvey');
    }

    return true;
}
