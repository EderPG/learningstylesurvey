<?php
require_once('../../config.php');
require_login();

global $DB;

$courseid = required_param('courseid', PARAM_INT);

echo "<h2>Verificación de Funcionalidades</h2>";

// Verificar versión del plugin
$plugin_version = $DB->get_field('config_plugins', 'value', 
    ['plugin' => 'mod_learningstylesurvey', 'name' => 'version']);
echo "<div style='background:#e7f3ff; padding:10px; margin:10px 0; border-left:4px solid #0066cc;'>";
echo "<strong>📋 Versión actual del plugin:</strong> " . ($plugin_version ? $plugin_version : 'No registrada') . "<br>";
echo "<strong>📅 Fecha:</strong> " . date('Y-m-d H:i:s') . "<br>";
echo "</div>";

// Verificar que las tablas existan
$tables_to_check = [
    'learningstylesurvey',
    'learningstylesurvey_temas',
    'learningstylesurvey_resources',
    'learningstylesurvey_quizzes',
    'learningstylesurvey_questions',
    'learningstylesurvey_options',
    'learningstylesurvey_quiz_results',
    'learningstylesurvey_paths',
    'learningstylesurvey_path_temas',
    'learningstylesurvey_path_files',
    'learningstylesurvey_path_evaluations',
    'learningpath_steps',
    'learningstylesurvey_user_progress'
];

echo "<h3>✅ Verificación de Tablas de Base de Datos</h3>";
echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
echo "<tr><th>Tabla</th><th>Estado</th><th>Registros</th></tr>";

foreach ($tables_to_check as $table) {
    $exists = $DB->get_manager()->table_exists($table);
    $count = $exists ? $DB->count_records($table) : 0;
    $status = $exists ? "✅ Existe" : "❌ No existe";
    echo "<tr><td>{$table}</td><td>{$status}</td><td>{$count}</td></tr>";
}
echo "</table>";

// Verificar funcionalidades básicas
echo "<h3>🔧 Verificación de Funcionalidades</h3>";

// 1. Verificar que se puedan crear temas
echo "<h4>1. Creación de Temas</h4>";
try {
    $test_tema = new stdClass();
    $test_tema->courseid = $courseid;
    $test_tema->tema = 'Tema de prueba - ' . time();
    $test_tema->timecreated = time();
    
    $tema_id = $DB->insert_record('learningstylesurvey_temas', $test_tema);
    echo "✅ Se pueden crear temas correctamente (ID: {$tema_id})<br>";
    
    // Limpiar
    $DB->delete_records('learningstylesurvey_temas', ['id' => $tema_id]);
    echo "✅ Se pueden eliminar temas correctamente<br>";
} catch (Exception $e) {
    echo "❌ Error al crear temas: " . $e->getMessage() . "<br>";
}

// 2. Verificar que se puedan crear recursos
echo "<h4>2. Creación de Recursos</h4>";
try {
    $test_resource = new stdClass();
    $test_resource->courseid = $courseid;
    $test_resource->name = 'Recurso de prueba';
    $test_resource->filename = 'test.pdf';
    $test_resource->style = 'visual';
    $test_resource->tema = null;
    
    $resource_id = $DB->insert_record('learningstylesurvey_resources', $test_resource);
    echo "✅ Se pueden crear recursos correctamente (ID: {$resource_id})<br>";
    
    // Limpiar
    $DB->delete_records('learningstylesurvey_resources', ['id' => $resource_id]);
    echo "✅ Se pueden eliminar recursos correctamente<br>";
} catch (Exception $e) {
    echo "❌ Error al crear recursos: " . $e->getMessage() . "<br>";
}

// 3. Verificar que se puedan crear quizzes
echo "<h4>3. Creación de Quizzes</h4>";
try {
    $test_quiz = new stdClass();
    $test_quiz->courseid = $courseid;
    $test_quiz->name = 'Quiz de prueba';
    $test_quiz->userid = $USER->id;
    $test_quiz->timecreated = time();
    
    $quiz_id = $DB->insert_record('learningstylesurvey_quizzes', $test_quiz);
    echo "✅ Se pueden crear quizzes correctamente (ID: {$quiz_id})<br>";
    
    // Limpiar
    $DB->delete_records('learningstylesurvey_quizzes', ['id' => $quiz_id]);
    echo "✅ Se pueden eliminar quizzes correctamente<br>";
} catch (Exception $e) {
    echo "❌ Error al crear quizzes: " . $e->getMessage() . "<br>";
}

// 4. Verificar que se puedan crear rutas (si la tabla existe)
echo "<h4>4. Creación de Rutas de Aprendizaje</h4>";
if ($DB->get_manager()->table_exists('learningstylesurvey_paths')) {
    try {
        $test_path = new stdClass();
        $test_path->courseid = $courseid;
        $test_path->userid = $USER->id;
        $test_path->name = 'Ruta de prueba';
        $test_path->filename = '';
        $test_path->timecreated = time();
        
        $path_id = $DB->insert_record('learningstylesurvey_paths', $test_path);
        echo "✅ Se pueden crear rutas correctamente (ID: {$path_id})<br>";
        
        // Probar relación con temas si la tabla existe
        if ($DB->get_manager()->table_exists('learningstylesurvey_path_temas')) {
            $test_tema = new stdClass();
            $test_tema->courseid = $courseid;
            $test_tema->tema = 'Tema para ruta de prueba';
            $test_tema->timecreated = time();
            $tema_id = $DB->insert_record('learningstylesurvey_temas', $test_tema);
            
            $test_path_tema = new stdClass();
            $test_path_tema->pathid = $path_id;
            $test_path_tema->temaid = $tema_id;
            $test_path_tema->orden = 1;
            $test_path_tema->isrefuerzo = 0;
            
            $path_tema_id = $DB->insert_record('learningstylesurvey_path_temas', $test_path_tema);
            echo "✅ Se pueden relacionar rutas con temas (ID: {$path_tema_id})<br>";
            
            // Limpiar
            $DB->delete_records('learningstylesurvey_path_temas', ['id' => $path_tema_id]);
            $DB->delete_records('learningstylesurvey_temas', ['id' => $tema_id]);
            echo "✅ Sistema de rutas adaptativas funcional<br>";
        } else {
            echo "⚠️ La tabla learningstylesurvey_path_temas no existe aún. Ejecute la actualización de la base de datos.<br>";
        }
        
        // Limpiar
        $DB->delete_records('learningstylesurvey_paths', ['id' => $path_id]);
        echo "✅ Se pueden eliminar rutas correctamente<br>";
    } catch (Exception $e) {
        echo "❌ Error al crear rutas: " . $e->getMessage() . "<br>";
    }
} else {
    echo "⚠️ La tabla learningstylesurvey_paths no existe<br>";
}

// 5. Verificar campos de timemodified
echo "<h4>5. Verificación de Campos de Base de Datos</h4>";
$resultstable = new xmldb_table('learningstylesurvey_quiz_results');
$timemodified_field = new xmldb_field('timemodified');
if ($DB->get_manager()->field_exists($resultstable, $timemodified_field)) {
    echo "✅ Campo timemodified existe en learningstylesurvey_quiz_results<br>";
} else {
    echo "⚠️ Campo timemodified no existe en learningstylesurvey_quiz_results. Ejecute la actualización de la base de datos.<br>";
}

echo "<h3>🔗 Enlaces de Prueba</h3>";
echo "<ul>";
$temas_url = new moodle_url('/mod/learningstylesurvey/temas.php', ['courseid' => $courseid]);
echo "<li><a href='" . $temas_url->out() . "'>Gestionar Temas</a></li>";
$upload_url = new moodle_url('/mod/learningstylesurvey/uploadresource.php', ['courseid' => $courseid]);
echo "<li><a href='" . $upload_url->out() . "'>Subir Recursos</a></li>";
$crear_url = new moodle_url('/mod/learningstylesurvey/crear_examen.php', ['courseid' => $courseid]);
echo "<li><a href='" . $crear_url->out() . "'>Crear Examen</a></li>";
$learning_url = new moodle_url('/mod/learningstylesurvey/learningpath.php', ['courseid' => $courseid]);
echo "<li><a href='" . $learning_url->out() . "'>Gestionar Rutas</a></li>";
$vista_url = new moodle_url('/mod/learningstylesurvey/vista_estudiante.php', ['courseid' => $courseid]);
echo "<li><a href='" . $vista_url->out() . "'>Vista de Estudiante</a></li>";
echo "</ul>";

echo "<h3>🔧 Instrucciones de Actualización</h3>";
echo "<div style='background:#f9f9f9; padding:15px; border-left:4px solid #0073e6;'>";
echo "<p><strong>Si ves advertencias arriba sobre tablas o campos faltantes:</strong></p>";
echo "<ol>";
echo "<li>Ve a <strong>Administración del sitio</strong> > <strong>Notificaciones</strong></li>";
echo "<li>O ve directamente a: <code>/admin/index.php</code></li>";
echo "<li>Moodle detectará automáticamente que el plugin necesita actualización</li>";
echo "<li>Haz clic en <strong>Actualizar base de datos ahora</strong></li>";
echo "<li>Vuelve a esta página para verificar que todo funcione correctamente</li>";
echo "</ol>";
echo "<p><strong>Versión actual del plugin:</strong> 2025082205</p>";
echo "</div>";

// Enlaces adicionales de debug
echo "<div style='background:#f8f9fa; padding:15px; border:1px solid #dee2e6; border-radius:5px; margin:20px 0;'>";
echo "<h3>🔧 Herramientas de Debug</h3>";
$debug_url = new moodle_url('/mod/learningstylesurvey/debug_saltos.php');
echo "<p><a href='" . $debug_url->out() . "' class='btn btn-secondary' style='padding:5px 10px; margin:5px; text-decoration:none; background:#6c757d; color:white; border-radius:3px;'>🔍 Debug Saltos y Refuerzos</a></p>";
echo "<p><small>Analiza el estado de los saltos adaptivos y temas de refuerzo en las rutas de aprendizaje</small></p>";
echo "</div>";

$view_url = new moodle_url('/mod/learningstylesurvey/view.php', ['id' => required_param('id', PARAM_INT)]);
echo "<p><a href='" . $view_url->out() . "'>← Volver al menú principal</a></p>";
?>
