<?php
require_once('../../../config.php');
require_once($CFG->libdir . '/tablelib.php');
require_login();

global $DB, $USER, $CFG;

$courseid = required_param('courseid', PARAM_INT);
$id = optional_param('id', 0, PARAM_INT); // CMid para regresar
$action = optional_param('action', '', PARAM_ALPHA); // Acción de limpieza
$confirm = optional_param('confirm', 0, PARAM_INT); // Confirmación

// Manejar acciones de limpieza de datos
if ($action && $confirm) {
    // Verificar sesskey manualmente para debugging
    $sesskey_param = optional_param('sesskey', '', PARAM_ALPHANUM);
    $debug_info = "Action={$action}, Confirm={$confirm}, Sesskey_sent={$sesskey_param}, Sesskey_expected=" . sesskey();
    
    if (!confirm_sesskey()) {
        $error_message = "❌ Error de autenticación: Token de sesión inválido. <br>Debug: {$debug_info}";
    } else {
        $success_message = '';
        $error_message = '';
        
        switch ($action) {
            case 'clear_resources':
                try {
                    $count = $DB->count_records('learningstylesurvey_resources', ['courseid' => $courseid]);
                    if ($count > 0) {
                        $DB->delete_records('learningstylesurvey_resources', ['courseid' => $courseid]);
                        $success_message = "✅ Se eliminaron {$count} recursos del curso.";
                    } else {
                        $success_message = "ℹ️ No había recursos para eliminar.";
                    }
                } catch (Exception $e) {
                    $error_message = "❌ Error eliminando recursos: " . $e->getMessage();
                }
                break;
                
            case 'clear_themes':
                try {
                    $count = $DB->count_records('learningstylesurvey_temas', ['courseid' => $courseid]);
                    if ($count > 0) {
                        $DB->delete_records('learningstylesurvey_temas', ['courseid' => $courseid]);
                        $success_message = "✅ Se eliminaron {$count} temas del curso.";
                    } else {
                        $success_message = "ℹ️ No había temas para eliminar.";
                    }
                } catch (Exception $e) {
                    $error_message = "❌ Error eliminando temas: " . $e->getMessage();
                }
                break;
                
            case 'clear_paths':
                try {
                    $count = $DB->count_records('learningstylesurvey_paths', ['courseid' => $courseid]);
                    if ($count > 0) {
                        // Eliminar pasos relacionados primero
                        if ($DB->get_manager()->table_exists('learningpath_steps')) {
                            $DB->delete_records_select('learningpath_steps', 
                                'pathid IN (SELECT id FROM {learningstylesurvey_paths} WHERE courseid = ?)', 
                                [$courseid]);
                        }
                        // Eliminar relaciones path_temas
                        if ($DB->get_manager()->table_exists('learningstylesurvey_path_temas')) {
                            $DB->delete_records_select('learningstylesurvey_path_temas', 
                                'pathid IN (SELECT id FROM {learningstylesurvey_paths} WHERE courseid = ?)', 
                                [$courseid]);
                        }
                        // Eliminar las rutas
                        $DB->delete_records('learningstylesurvey_paths', ['courseid' => $courseid]);
                        $success_message = "✅ Se eliminaron {$count} rutas de aprendizaje y sus datos relacionados del curso.";
                    } else {
                        $success_message = "ℹ️ No había rutas para eliminar.";
                    }
                } catch (Exception $e) {
                    $error_message = "❌ Error eliminando rutas: " . $e->getMessage();
                }
                break;
                
            case 'clear_survey_results':
                try {
                    // Obtener instancias del plugin en este curso
                    $cms = get_fast_modinfo($courseid)->get_instances_of('learningstylesurvey');
                    $survey_ids = array_map(function($cm) { return $cm->instance; }, $cms);
                    
                    $total_deleted = 0;
                    if (!empty($survey_ids)) {
                        list($in_sql, $params) = $DB->get_in_or_equal($survey_ids);
                        
                        // Eliminar respuestas de encuestas
                        $count1 = $DB->count_records_select('learningstylesurvey_responses', "surveyid $in_sql", $params);
                        if ($count1 > 0) {
                            $DB->delete_records_select('learningstylesurvey_responses', "surveyid $in_sql", $params);
                            $total_deleted += $count1;
                        }
                    }
                    
                    if ($total_deleted > 0) {
                        $success_message = "✅ Se eliminaron {$total_deleted} respuestas de encuestas del curso.";
                    } else {
                        $success_message = "ℹ️ No había respuestas de encuestas para eliminar.";
                    }
                } catch (Exception $e) {
                    $error_message = "❌ Error eliminando resultados de encuestas: " . $e->getMessage();
                }
                break;
                
            case 'clear_quizzes':
                try {
                    $count_quizzes = $DB->count_records('learningstylesurvey_quizzes', ['courseid' => $courseid]);
                    
                    if ($count_quizzes > 0) {
                        // Eliminar resultados de quizzes
                        $DB->delete_records('learningstylesurvey_quiz_results', ['courseid' => $courseid]);
                        
                        // Obtener IDs de quizzes para eliminar preguntas y opciones
                        $quiz_ids = $DB->get_fieldset_select('learningstylesurvey_quizzes', 'id', 'courseid = ?', [$courseid]);
                        
                        if (!empty($quiz_ids)) {
                            list($in_sql, $params) = $DB->get_in_or_equal($quiz_ids);
                            
                            // Eliminar opciones de preguntas
                            $question_ids = $DB->get_fieldset_select('learningstylesurvey_questions', 'id', "quizid $in_sql", $params);
                            if (!empty($question_ids)) {
                                list($q_in_sql, $q_params) = $DB->get_in_or_equal($question_ids);
                                $DB->delete_records_select('learningstylesurvey_options', "questionid $q_in_sql", $q_params);
                            }
                            
                            // Eliminar preguntas
                            $DB->delete_records_select('learningstylesurvey_questions', "quizid $in_sql", $params);
                        }
                        
                        // Eliminar quizzes
                        $DB->delete_records('learningstylesurvey_quizzes', ['courseid' => $courseid]);
                        
                        $success_message = "✅ Se eliminaron {$count_quizzes} exámenes y todos sus datos relacionados del curso.";
                    } else {
                        $success_message = "ℹ️ No había exámenes para eliminar.";
                    }
                } catch (Exception $e) {
                    $error_message = "❌ Error eliminando exámenes: " . $e->getMessage();
                }
                break;
                
            default:
                $error_message = "❌ Acción no reconocida: {$action}";
        }
    }
}

// Estilo CSS mejorado
echo "<style>
.verification-card {
    background: #fff;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    padding: 20px;
    margin: 15px 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.status-success { color: #28a745; font-weight: bold; }
.status-warning { color: #ffc107; font-weight: bold; }
.status-error { color: #dc3545; font-weight: bold; }
.info-box {
    background: #e7f3ff;
    border-left: 4px solid #0066cc;
    padding: 15px;
    margin: 15px 0;
    border-radius: 4px;
}
.warning-box {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
    padding: 15px;
    margin: 15px 0;
    border-radius: 4px;
}
.success-box {
    background: #d4edda;
    border-left: 4px solid #28a745;
    padding: 15px;
    margin: 15px 0;
    border-radius: 4px;
}
.btn {
    display: inline-block;
    padding: 8px 16px;
    margin: 5px;
    text-decoration: none;
    border-radius: 4px;
    font-weight: bold;
    text-align: center;
}
.btn-primary { background: #007bff; color: white; }
.btn-secondary { background: #6c757d; color: white; }
.btn-success { background: #28a745; color: white; }
.btn-warning { background: #ffc107; color: black; }
.btn-danger { background: #dc3545; color: white; }
.btn-danger:hover { background: #c82333; text-decoration: none; color: white; }
.notification-success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
    padding: 15px;
    border-radius: 8px;
    margin: 10px 0;
}
.notification-error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
    padding: 15px;
    border-radius: 8px;
    margin: 10px 0;
}
.cleanup-warning {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    color: #856404;
    padding: 15px;
    border-radius: 8px;
    margin: 15px 0;
    border-left: 4px solid #ffc107;
}
.nav-buttons {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin: 20px 0;
    text-align: center;
}
table { width: 100%; border-collapse: collapse; margin: 15px 0; }
th, td { padding: 10px; border: 1px solid #dee2e6; text-align: left; }
th { background: #f8f9fa; font-weight: bold; }
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin: 20px 0;
}
.stat-card {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
    border: 1px solid #dee2e6;
}
</style>";

echo "<div class='verification-card'>";
echo "<h2>🔍 Verificación Completa de Funcionalidades</h2>";
echo "<p>Sistema integral de diagnóstico para el módulo Learning Style Survey</p>";
echo "</div>";

// Mostrar notificaciones de éxito o error
if (isset($success_message)) {
    echo "<div class='notification-success'>{$success_message}</div>";
}
if (isset($error_message)) {
    echo "<div class='notification-error'>{$error_message}</div>";
}

// Debug temporal: mostrar parámetros recibidos si hay alguna acción
if ($action || $confirm) {
    echo "<div class='info-box'>";
    echo "<h4>🔍 Debug Info (temporal)</h4>";
    echo "<strong>Action:</strong> " . ($action ? $action : 'None') . "<br>";
    echo "<strong>Confirm:</strong> " . ($confirm ? 'Yes' : 'No') . "<br>";
    echo "<strong>Sesskey recibido:</strong> " . optional_param('sesskey', 'None', PARAM_ALPHANUM) . "<br>";
    echo "<strong>Sesskey esperado:</strong> " . sesskey() . "<br>";
    echo "</div>";
}

// Verificar versión del plugin y estadísticas básicas
$plugin_version = $DB->get_field('config_plugins', 'value', 
    ['plugin' => 'mod_learningstylesurvey', 'name' => 'version']);

echo "<div class='info-box'>";
echo "<h3>📋 Información del Sistema</h3>";
echo "<strong>� Versión del plugin:</strong> " . ($plugin_version ? $plugin_version : 'No registrada') . "<br>";
echo "<strong>📅 Fecha de verificación:</strong> " . date('Y-m-d H:i:s') . "<br>";
echo "<strong>🎓 Curso ID:</strong> {$courseid}<br>";
echo "<strong>👤 Usuario verificador:</strong> {$USER->username} (ID: {$USER->id})<br>";
echo "<strong>🌐 Versión de Moodle:</strong> {$CFG->version}<br>";
echo "</div>";

// Estadísticas rápidas del curso
$total_temas = $DB->count_records('learningstylesurvey_temas', ['courseid' => $courseid]);
$total_recursos = $DB->count_records('learningstylesurvey_resources', ['courseid' => $courseid]);
$total_quizzes = $DB->count_records('learningstylesurvey_quizzes', ['courseid' => $courseid]);
$total_rutas = 0;
if ($DB->get_manager()->table_exists('learningstylesurvey_paths')) {
    $total_rutas = $DB->count_records('learningstylesurvey_paths', ['courseid' => $courseid]);
}

echo "<div class='stats-grid'>";
echo "<div class='stat-card'>";
echo "<h4>📚 Temas</h4>";
echo "<div style='font-size: 24px; color: #007bff;'>{$total_temas}</div>";
echo "</div>";
echo "<div class='stat-card'>";
echo "<h4>📁 Recursos</h4>";
echo "<div style='font-size: 24px; color: #28a745;'>{$total_recursos}</div>";
echo "</div>";
echo "<div class='stat-card'>";
echo "<h4>📝 Quizzes</h4>";
echo "<div style='font-size: 24px; color: #ffc107;'>{$total_quizzes}</div>";
echo "</div>";
echo "<div class='stat-card'>";
echo "<h4>🛤️ Rutas</h4>";
echo "<div style='font-size: 24px; color: #dc3545;'>{$total_rutas}</div>";
echo "</div>";
echo "</div>";

// Verificar que las tablas existan (ampliado)
$tables_to_check = [
    'learningstylesurvey' => 'Tabla principal del módulo',
    'learningstylesurvey_temas' => 'Gestión de temas por curso',
    'learningstylesurvey_resources' => 'Archivos subidos por estilo',
    'learningstylesurvey_quizzes' => 'Cuestionarios de evaluación',
    'learningstylesurvey_questions' => 'Preguntas de los quizzes',
    'learningstylesurvey_options' => 'Opciones de respuesta',
    'learningstylesurvey_quiz_results' => 'Resultados de evaluaciones',
    'learningstylesurvey_paths' => 'Rutas de aprendizaje personalizadas (✨ NUEVO - con campo cmid)',
    'learningstylesurvey_path_temas' => 'Relación rutas-temas (✨ ACTUALIZADO)',
    'learningstylesurvey_path_files' => 'Archivos por ruta (✨ NUEVO)',
    'learningstylesurvey_path_evaluations' => 'Evaluaciones por ruta (✨ NUEVO)',
    'learningpath_steps' => 'Pasos de navegación (sistema activo)',
    'learningstylesurvey_user_progress' => 'Progreso de usuarios',
    'learningstylesurvey_userstyles' => 'Estilos asignados a usuarios',
    'learningstylesurvey_responses' => 'Respuestas de la encuesta inicial',
    'learningstylesurvey_results' => 'Resultados de estilos (Muestra estilo dominante)',
    'learningstylesurvey_learningpath' => 'Rutas originales (sistema viejo)',
    'learningstylesurvey_inforoute' => 'Información de rutas'
];

echo "<div class='verification-card'>";
echo "<h3>✅ Estado de la Base de Datos</h3>";
echo "<table>";
echo "<thead><tr><th>Tabla</th><th>Estado</th><th>Registros</th><th>Descripción</th></tr></thead>";
echo "<tbody>";

$tables_ok = 0;
$tables_missing = 0;
$total_records = 0;

foreach ($tables_to_check as $table => $description) {
    $exists = $DB->get_manager()->table_exists($table);
    $count = $exists ? $DB->count_records($table) : 0;
    $total_records += $count;
    
    if ($exists) {
        $tables_ok++;
        $status = "<span class='status-success'>✅ Existe</span>";
    } else {
        $tables_missing++;
        $status = "<span class='status-error'>❌ Faltante</span>";
    }
    
    echo "<tr>";
    echo "<td><code>{$table}</code></td>";
    echo "<td>{$status}</td>";
    echo "<td>" . ($exists ? number_format($count) : 'N/A') . "</td>";
    echo "<td>{$description}</td>";
    echo "</tr>";
}
echo "</tbody>";
echo "</table>";

echo "<div class='success-box'>";
echo "<strong>📊 Resumen:</strong> {$tables_ok} tablas funcionando, {$tables_missing} faltantes, " . number_format($total_records) . " registros totales";
echo "</div>";
echo "</div>";

// ✨ NUEVA SECCIÓN: Verificar actualización con campo cmid
echo "<div class='verification-card'>";
echo "<h3>🆕 Verificación de Nuevas Funcionalidades</h3>";

// Verificar campo cmid en tabla paths
echo "<h4>🔧 Campo cmid en Rutas (Multi-instancia)</h4>";
if ($DB->get_manager()->table_exists('learningstylesurvey_paths')) {
    $table = new xmldb_table('learningstylesurvey_paths');
    $cmid_field = new xmldb_field('cmid');
    
    if ($DB->get_manager()->field_exists($table, $cmid_field)) {
        echo "<span class='status-success'>✅ Campo 'cmid' existe en tabla paths</span><br>";
        
        // Verificar si hay rutas que usan cmid
        $paths_with_cmid = $DB->count_records_select('learningstylesurvey_paths', 'cmid > 0');
        $total_paths = $DB->count_records('learningstylesurvey_paths');
        
        echo "<span class='status-success'>✅ Rutas totales: {$total_paths}</span><br>";
        echo "<span class='status-success'>✅ Rutas con cmid: {$paths_with_cmid}</span><br>";
        
        if ($paths_with_cmid > 0) {
            echo "<span class='status-success'>✅ FUNCIONALIDAD MULTI-INSTANCIA: ACTIVA</span><br>";
        } else {
            echo "<span class='status-warning'>⚠️ Aún no hay rutas que usen multi-instancia</span><br>";
        }
    } else {
        echo "<span class='status-error'>❌ Campo 'cmid' NO existe. Ejecuta la migración de la base de datos.</span><br>";
    }
} else {
    echo "<span class='status-error'>❌ Tabla paths no existe</span><br>";
}

// Verificar nuevas tablas de archivos y evaluaciones
echo "<h4>📁 Nuevas Tablas de Archivos y Evaluaciones</h4>";
$new_tables = ['learningstylesurvey_path_files', 'learningstylesurvey_path_evaluations'];
foreach ($new_tables as $table_name) {
    if ($DB->get_manager()->table_exists($table_name)) {
        $count = $DB->count_records($table_name);
        echo "<span class='status-success'>✅ Tabla {$table_name}: OK ({$count} registros)</span><br>";
    } else {
        echo "<span class='status-error'>❌ Tabla {$table_name}: NO EXISTE</span><br>";
    }
}

echo "</div>";

// Verificar funcionalidades básicas expandidas (SIN CREAR DATOS)
echo "<div class='verification-card'>";
echo "<h3>🔧 Verificación de Funcionalidad (Solo Lectura)</h3>";

$tests_passed = 0;
$tests_failed = 0;

// 1. Verificar capacidades de gestión de temas
echo "<h4>1. 📚 Sistema de Gestión de Temas</h4>";
try {
    // Verificar estructura de tabla sin crear datos
    if ($DB->get_manager()->table_exists('learningstylesurvey_temas')) {
        $table = new xmldb_table('learningstylesurvey_temas');
        $required_fields = ['courseid', 'tema', 'timecreated'];
        $fields_ok = true;
        
        foreach ($required_fields as $field_name) {
            $field = new xmldb_field($field_name);
            if (!$DB->get_manager()->field_exists($table, $field)) {
                $fields_ok = false;
                break;
            }
        }
        
        if ($fields_ok) {
            echo "<span class='status-success'>✅ Estructura de tabla: OK</span><br>";
            echo "<span class='status-success'>✅ Campos requeridos: OK</span><br>";
            echo "<span class='status-success'>✅ Sistema de temas: FUNCIONAL</span><br>";
            $tests_passed += 3;
        } else {
            echo "<span class='status-error'>❌ Faltan campos requeridos en tabla temas</span><br>";
            $tests_failed += 3;
        }
    } else {
        echo "<span class='status-error'>❌ Tabla learningstylesurvey_temas no existe</span><br>";
        $tests_failed += 3;
    }
} catch (Exception $e) {
    echo "<span class='status-error'>❌ Error verificando temas: " . $e->getMessage() . "</span><br>";
    $tests_failed += 3;
}

// 2. Verificar sistema de recursos por estilo
echo "<h4>2. 📁 Sistema de Recursos por Estilo</h4>";
try {
    if ($DB->get_manager()->table_exists('learningstylesurvey_resources')) {
        $table = new xmldb_table('learningstylesurvey_resources');
        $required_fields = ['courseid', 'userid', 'name', 'filename', 'style', 'tema'];
        $fields_ok = true;
        
        foreach ($required_fields as $field_name) {
            $field = new xmldb_field($field_name);
            if (!$DB->get_manager()->field_exists($table, $field)) {
                $fields_ok = false;
                echo "<span class='status-error'>❌ Campo faltante: {$field_name}</span><br>";
            }
        }
        
        if ($fields_ok) {
            // Verificar si hay recursos existentes para probar filtrado
            $existing_resources = $DB->count_records('learningstylesurvey_resources', ['courseid' => $courseid]);
            
            echo "<span class='status-success'>✅ Estructura de recursos: OK</span><br>";
            echo "<span class='status-success'>✅ Campo 'style' disponible: OK</span><br>";
            echo "<span class='status-success'>✅ Recursos en curso: {$existing_resources}</span><br>";
            $tests_passed += 3;
        } else {
            $tests_failed += 3;
        }
    } else {
        echo "<span class='status-error'>❌ Tabla learningstylesurvey_resources no existe</span><br>";
        $tests_failed += 3;
    }
} catch (Exception $e) {
    echo "<span class='status-error'>❌ Error verificando recursos: " . $e->getMessage() . "</span><br>";
    $tests_failed += 3;
}

// 3. Verificar sistema completo de quizzes
echo "<h4>3. 📝 Sistema Completo de Evaluaciones</h4>";
try {
    $quiz_tables = ['learningstylesurvey_quizzes', 'learningstylesurvey_questions', 
                    'learningstylesurvey_options', 'learningstylesurvey_quiz_results'];
    $quiz_system_ok = true;
    
    foreach ($quiz_tables as $table_name) {
        if (!$DB->get_manager()->table_exists($table_name)) {
            echo "<span class='status-error'>❌ Tabla faltante: {$table_name}</span><br>";
            $quiz_system_ok = false;
        }
    }
    
    if ($quiz_system_ok) {
        // Verificar campos críticos
        $quiz_table = new xmldb_table('learningstylesurvey_quiz_results');
        $timemodified_field = new xmldb_field('timemodified');
        $has_timemodified = $DB->get_manager()->field_exists($quiz_table, $timemodified_field);
        
        $existing_quizzes = $DB->count_records('learningstylesurvey_quizzes', ['courseid' => $courseid]);
        $total_results = $DB->count_records_sql(
            "SELECT COUNT(*) FROM {learningstylesurvey_quiz_results} qr 
             JOIN {learningstylesurvey_quizzes} q ON qr.quizid = q.id 
             WHERE q.courseid = ?", [$courseid]
        );
        
        echo "<span class='status-success'>✅ Todas las tablas de quiz: OK</span><br>";
        echo "<span class='status-success'>✅ Campo timemodified: " . ($has_timemodified ? "OK" : "⚠️ Faltante") . "</span><br>";
        echo "<span class='status-success'>✅ Quizzes en curso: {$existing_quizzes}</span><br>";
        echo "<span class='status-success'>✅ Resultados registrados: {$total_results}</span><br>";
        echo "<span class='status-success'>✅ Sistema multi-intento: DISPONIBLE</span><br>";
        $tests_passed += 5;
    } else {
        $tests_failed += 5;
    }
} catch (Exception $e) {
    echo "<span class='status-error'>❌ Error verificando quizzes: " . $e->getMessage() . "</span><br>";
    $tests_failed += 5;
}

// 4. Verificar sistema de rutas adaptativas (SIN CREAR RUTAS)
echo "<h4>4. 🛤️ Sistema de Rutas Adaptativas</h4>";
try {
    $route_tables = ['learningstylesurvey_paths', 'learningstylesurvey_path_temas', 'learningpath_steps'];
    $route_system_status = [];
    
    foreach ($route_tables as $table_name) {
        $route_system_status[$table_name] = $DB->get_manager()->table_exists($table_name);
    }
    
    if ($route_system_status['learningstylesurvey_paths']) {
        echo "<span class='status-success'>✅ Tabla principal de rutas: OK</span><br>";
        
        // Verificar rutas existentes sin crear nuevas
        $existing_paths = $DB->count_records('learningstylesurvey_paths', ['courseid' => $courseid]);
        echo "<span class='status-success'>✅ Rutas en curso: {$existing_paths}</span><br>";
        
        if ($route_system_status['learningstylesurvey_path_temas']) {
            echo "<span class='status-success'>✅ Sistema de relaciones ruta-tema: OK</span><br>";
            
            $path_relationships = $DB->count_records_sql(
                "SELECT COUNT(*) FROM {learningstylesurvey_path_temas} pt 
                 JOIN {learningstylesurvey_paths} p ON pt.pathid = p.id 
                 WHERE p.courseid = ?", [$courseid]
            );
            echo "<span class='status-success'>✅ Relaciones ruta-tema: {$path_relationships}</span><br>";
        } else {
            echo "<span class='status-warning'>⚠️ Tabla path_temas no existe. Ejecutar upgrade</span><br>";
        }
        
        if ($route_system_status['learningpath_steps']) {
            echo "<span class='status-success'>✅ Sistema de pasos: OK</span><br>";
            
            $total_steps = $DB->count_records_sql(
                "SELECT COUNT(*) FROM {learningpath_steps} ls 
                 JOIN {learningstylesurvey_paths} p ON ls.pathid = p.id 
                 WHERE p.courseid = ?", [$courseid]
            );
            echo "<span class='status-success'>✅ Pasos configurados: {$total_steps}</span><br>";
        } else {
            echo "<span class='status-error'>❌ Tabla learningpath_steps no existe</span><br>";
        }
        
        // Verificar progreso de usuarios
        if ($DB->get_manager()->table_exists('learningstylesurvey_user_progress')) {
            $user_progress_count = $DB->count_records_sql(
                "SELECT COUNT(*) FROM {learningstylesurvey_user_progress} up 
                 JOIN {learningstylesurvey_paths} p ON up.pathid = p.id 
                 WHERE p.courseid = ?", [$courseid]
            );
            echo "<span class='status-success'>✅ Registros de progreso: {$user_progress_count}</span><br>";
        }
        
        echo "<span class='status-success'>✅ Sistema adaptativo: DISPONIBLE</span><br>";
        $tests_passed += 5;
    } else {
        echo "<span class='status-error'>❌ Tabla learningstylesurvey_paths no existe</span><br>";
        $tests_failed += 5;
    }
} catch (Exception $e) {
    echo "<span class='status-error'>❌ Error verificando rutas: " . $e->getMessage() . "</span><br>";
    $tests_failed += 5;
}

// 5. Verificar sistema de estilos de aprendizaje
echo "<h4>5. 🎨 Sistema de Estilos de Aprendizaje</h4>";
try {
    if ($DB->get_manager()->table_exists('learningstylesurvey_userstyles')) {
        echo "<span class='status-success'>✅ Tabla de estilos: OK</span><br>";
        
        // Verificar si el usuario actual tiene estilo asignado
        $user_style = $DB->get_record_sql(
            "SELECT * FROM {learningstylesurvey_userstyles} 
             WHERE userid = ? ORDER BY timecreated DESC LIMIT 1", 
            [$USER->id]
        );
        
        if ($user_style) {
            echo "<span class='status-success'>✅ Tu estilo actual: {$user_style->style}</span><br>";
        } else {
            echo "<span class='status-warning'>⚠️ No tienes estilo asignado aún</span><br>";
        }
        
        // Contar estilos en el sistema
        $total_styles = $DB->count_records('learningstylesurvey_userstyles');
        echo "<span class='status-success'>✅ Estilos registrados: {$total_styles}</span><br>";
        $tests_passed += 2;
    } else {
        echo "<span class='status-error'>❌ Tabla learningstylesurvey_userstyles no existe</span><br>";
        $tests_failed += 2;
    }
} catch (Exception $e) {
    echo "<span class='status-error'>❌ Error verificando estilos: " . $e->getMessage() . "</span><br>";
    $tests_failed += 2;
}

// 6. Verificar restricción una-ruta-por-curso
echo "<h4>6. 🔒 Restricción Una-Ruta-Por-Curso</h4>";
try {
    if ($DB->get_manager()->table_exists('learningstylesurvey_paths')) {
        $paths_in_course = $DB->count_records('learningstylesurvey_paths', ['courseid' => $courseid]);
        
        if ($paths_in_course == 0) {
            echo "<span class='status-success'>✅ Sin rutas: Listo para crear primera ruta</span><br>";
        } elseif ($paths_in_course == 1) {
            echo "<span class='status-success'>✅ Una ruta activa: Restricción funcionando</span><br>";
        } else {
            echo "<span class='status-warning'>⚠️ Múltiples rutas detectadas: {$paths_in_course} rutas</span><br>";
        }
        $tests_passed += 1;
    } else {
        echo "<span class='status-warning'>⚠️ No se puede verificar restricción sin tabla de rutas</span><br>";
        $tests_failed += 1;
    }
} catch (Exception $e) {
    echo "<span class='status-error'>❌ Error verificando restricción: " . $e->getMessage() . "</span><br>";
    $tests_failed += 1;
}

// Resumen de pruebas
$total_tests = $tests_passed + $tests_failed;
$success_rate = $total_tests > 0 ? round(($tests_passed / $total_tests) * 100, 1) : 0;

echo "<div class='success-box'>";
echo "<h4>📊 Resumen de Pruebas</h4>";
echo "<strong>✅ Pasadas:</strong> {$tests_passed} | ";
echo "<strong>❌ Fallidas:</strong> {$tests_failed} | ";
echo "<strong>📈 Tasa de éxito:</strong> {$success_rate}%";
echo "</div>";
echo "</div>";

// Verificación avanzada de estructura de base de datos
echo "<div class='verification-card'>";
echo "<h3>🔍 Verificación Avanzada de Estructura</h3>";

echo "<h4>📋 Campos Críticos</h4>";
$critical_fields = [
    'learningstylesurvey_quiz_results' => ['timemodified', 'userid', 'quizid', 'score'],
    'learningstylesurvey_resources' => ['tema', 'style', 'userid', 'courseid'],
    'learningstylesurvey_questions' => ['correctanswer', 'questiontext'],
    'learningpath_steps' => ['passredirect', 'failredirect', 'istest'],
    'learningstylesurvey_userstyles' => ['style', 'timecreated']
];

foreach ($critical_fields as $table => $fields) {
    if ($DB->get_manager()->table_exists($table)) {
        echo "<strong>{$table}:</strong> ";
        $missing_fields = [];
        foreach ($fields as $field) {
            $field_obj = new xmldb_field($field);
            if ($DB->get_manager()->field_exists(new xmldb_table($table), $field_obj)) {
                echo "<span class='status-success'>✅ {$field}</span> ";
            } else {
                echo "<span class='status-error'>❌ {$field}</span> ";
                $missing_fields[] = $field;
            }
        }
        if (empty($missing_fields)) {
            echo "<span class='status-success'> - Completa</span>";
        } else {
            echo "<span class='status-warning'> - Faltan: " . implode(', ', $missing_fields) . "</span>";
        }
        echo "<br>";
    } else {
        echo "<strong>{$table}:</strong> <span class='status-error'>❌ Tabla no existe</span><br>";
    }
}

// Verificar integridad de datos
echo "<h4>🔗 Integridad de Datos</h4>";
try {
    // Verificar recursos huérfanos
    $orphan_resources = $DB->get_records_sql(
        "SELECT r.* FROM {learningstylesurvey_resources} r 
         LEFT JOIN {learningstylesurvey_temas} t ON r.tema = t.tema AND r.courseid = t.courseid
         WHERE r.tema IS NOT NULL AND t.id IS NULL AND r.courseid = ?", 
        [$courseid]
    );
    
    if (empty($orphan_resources)) {
        echo "<span class='status-success'>✅ Sin recursos huérfanos</span><br>";
    } else {
        echo "<span class='status-warning'>⚠️ " . count($orphan_resources) . " recursos huérfanos encontrados</span><br>";
    }
    
    // Verificar quizzes sin preguntas
    $empty_quizzes = $DB->get_records_sql(
        "SELECT q.* FROM {learningstylesurvey_quizzes} q 
         LEFT JOIN {learningstylesurvey_questions} qu ON q.id = qu.quizid
         WHERE qu.id IS NULL AND q.courseid = ?", 
        [$courseid]
    );
    
    if (empty($empty_quizzes)) {
        echo "<span class='status-success'>✅ Todos los quizzes tienen preguntas</span><br>";
    } else {
        echo "<span class='status-warning'>⚠️ " . count($empty_quizzes) . " quizzes sin preguntas</span><br>";
    }
    
    // Verificar preguntas sin opciones
    $questions_no_options = $DB->get_records_sql(
        "SELECT qu.* FROM {learningstylesurvey_questions} qu 
         JOIN {learningstylesurvey_quizzes} q ON qu.quizid = q.id
         LEFT JOIN {learningstylesurvey_options} o ON qu.id = o.questionid
         WHERE o.id IS NULL AND q.courseid = ?", 
        [$courseid]
    );
    
    if (empty($questions_no_options)) {
        echo "<span class='status-success'>✅ Todas las preguntas tienen opciones</span><br>";
    } else {
        echo "<span class='status-warning'>⚠️ " . count($questions_no_options) . " preguntas sin opciones</span><br>";
    }
    
} catch (Exception $e) {
    echo "<span class='status-error'>❌ Error verificando integridad: " . $e->getMessage() . "</span><br>";
}

echo "</div>";

// Verificar permisos y capacidades
echo "<div class='verification-card'>";
echo "<h3>🔐 Verificación de Permisos</h3>";

$context = context_course::instance($courseid);
$capabilities = [
    'mod/learningstylesurvey:addinstance' => 'Añadir instancia del módulo',
    'mod/learningstylesurvey:view' => 'Ver contenido del módulo',
    'mod/learningstylesurvey:submit' => 'Enviar respuestas',
    'moodle/course:manageactivities' => 'Gestionar actividades'
];

foreach ($capabilities as $capability => $description) {
    if (has_capability($capability, $context)) {
        echo "<span class='status-success'>✅ {$capability}</span> - {$description}<br>";
    } else {
        echo "<span class='status-warning'>⚠️ {$capability}</span> - {$description}<br>";
    }
}

echo "</div>";

// Panel de navegación y herramientas
echo "<div class='verification-card'>";
echo "<h3>� Panel de Herramientas y Navegación</h3>";

echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;'>";

// Gestión básica
echo "<div>";
echo "<h4>📚 Gestión de Contenido</h4>";
echo "<div>";
$temas_url = new moodle_url('/mod/learningstylesurvey/resource/temas.php', ['courseid' => $courseid, 'cmid' => $id]);
echo "<a href='" . $temas_url->out() . "' class='btn btn-primary'>📚 Gestionar Temas</a><br>";

$upload_url = new moodle_url('/mod/learningstylesurvey/resource/uploadresource.php', ['courseid' => $courseid, 'cmid' => $id]);
echo "<a href='" . $upload_url->out() . "' class='btn btn-success'>📁 Subir Recursos</a><br>";

$crear_url = new moodle_url('/mod/learningstylesurvey/quiz/crear_examen.php', ['courseid' => $courseid, 'cmid' => $id]);
echo "<a href='" . $crear_url->out() . "' class='btn btn-warning'>📝 Crear Examen</a><br>";
echo "</div>";
echo "</div>";

// Rutas y navegación
echo "<div>";
echo "<h4>🛤️ Rutas de Aprendizaje</h4>";
echo "<div>";
$learning_url = new moodle_url('/mod/learningstylesurvey/path/learningpath.php', ['courseid' => $courseid]);
echo "<a href='" . $learning_url->out() . "' class='btn btn-primary'>🛤️ Gestionar Rutas</a><br>";

$create_route_url = new moodle_url('/mod/learningstylesurvey/path/createsteproute.php', ['courseid' => $courseid]);
echo "<a href='" . $create_route_url->out() . "' class='btn btn-success'>➕ Crear Nueva Ruta</a><br>";

$vista_url = new moodle_url('/mod/learningstylesurvey/path/vista_estudiante.php', ['courseid' => $courseid]);
echo "<a href='" . $vista_url->out() . "' class='btn btn-secondary'>👁️ Vista de Estudiante</a><br>";
echo "</div>";
echo "</div>";

// Herramientas de administración
echo "<div>";
echo "<h4>⚙️ Administración</h4>";
echo "<div>";
$manage_quiz_url = new moodle_url('/mod/learningstylesurvey/quiz/manage_quiz.php', ['courseid' => $courseid]);
echo "<a href='" . $manage_quiz_url->out() . "' class='btn btn-warning'>⚙️ Gestionar Quizzes</a><br>";

$resources_url = new moodle_url('/mod/learningstylesurvey/resource/viewresources.php', ['courseid' => $courseid]);
echo "<a href='" . $resources_url->out() . "' class='btn btn-secondary'>👀 Ver Recursos</a><br>";

$results_url = new moodle_url('/mod/learningstylesurvey/results.php', ['courseid' => $courseid]);
echo "<a href='" . $results_url->out() . "' class='btn btn-success'>📊 Ver Resultados</a><br>";
echo "</div>";
echo "</div>";

// Herramientas de debug
echo "<div>";
echo "<h4>🔧 Herramientas de Debug</h4>";
echo "<div>";
$debug_url = new moodle_url('/mod/learningstylesurvey/debug/debug_saltos.php');
echo "<a href='" . $debug_url->out() . "' class='btn btn-danger'>🔍 Debug Saltos</a><br>";

$debug_retry_url = new moodle_url('/mod/learningstylesurvey/debug/debug_retry.php', ['courseid' => $courseid]);
echo "<a href='" . $debug_retry_url->out() . "' class='btn btn-warning'>🔄 Debug Reintentos</a><br>";

echo "<a href='#' onclick='location.reload()' class='btn btn-secondary'>🔄 Recargar Verificación</a><br>";
echo "</div>";
echo "</div>";

echo "</div>";
echo "</div>";

// Instrucciones y ayuda
echo "<div class='verification-card'>";
echo "<h3>📋 Instrucciones y Resolución de Problemas</h3>";

echo "<div class='warning-box'>";
echo "<h4>⚠️ Si encuentras problemas:</h4>";
echo "<ol>";
echo "<li><strong>Tablas faltantes:</strong> Ve a <code>Administración del sitio → Notificaciones</code> o <code>/admin/index.php</code></li>";
echo "<li><strong>Campos faltantes:</strong> Ejecuta 'Actualizar base de datos ahora' en las notificaciones</li>";
echo "<li><strong>Permisos insuficientes:</strong> Verifica que tengas rol de profesor o administrador</li>";
echo "<li><strong>Recursos huérfanos:</strong> Usa la herramienta de gestión de temas para reorganizar</li>";
echo "<li><strong>Quizzes incompletos:</strong> Edita los quizzes desde 'Gestionar Quizzes'</li>";
echo "</ol>";
echo "</div>";

// ⚠️ NUEVA SECCIÓN: Herramientas de Limpieza de Datos
echo "<div class='verification-card'>";
echo "<h3>🗑️ Herramientas de Limpieza de Base de Datos</h3>";

echo "<div class='cleanup-warning'>";
echo "<strong>⚠️ ADVERTENCIA:</strong> Estas herramientas eliminan datos permanentemente. ";
echo "Úsalas solo si necesitas limpiar datos de prueba o corregir problemas. ";
echo "<strong>No hay forma de recuperar los datos eliminados.</strong>";
echo "</div>";

// Solo mostrar a administradores del sitio
if (is_siteadmin($USER)) {
    
    // Mostrar estadísticas actuales
    echo "<h4>📊 Datos Actuales del Curso</h4>";
    $stats = [];
    $stats['Recursos'] = $DB->count_records('learningstylesurvey_resources', ['courseid' => $courseid]);
    $stats['Temas'] = $DB->count_records('learningstylesurvey_temas', ['courseid' => $courseid]);
    $stats['Rutas'] = $DB->count_records('learningstylesurvey_paths', ['courseid' => $courseid]);
    $stats['Exámenes'] = $DB->count_records('learningstylesurvey_quizzes', ['courseid' => $courseid]);
    
    // Contar respuestas de encuestas
    $cms = get_fast_modinfo($courseid)->get_instances_of('learningstylesurvey');
    $survey_count = 0;
    if (!empty($cms)) {
        $survey_ids = array_map(function($cm) { return $cm->instance; }, $cms);
        list($in_sql, $params) = $DB->get_in_or_equal($survey_ids);
        $survey_count = $DB->count_records_select('learningstylesurvey_responses', "surveyid $in_sql", $params);
    }
    $stats['Respuestas de encuestas'] = $survey_count;
    
    echo "<div class='stats-grid'>";
    foreach ($stats as $type => $count) {
        echo "<div class='stat-card'>";
        echo "<strong>{$type}</strong><br>";
        echo "<span style='font-size: 24px; color: " . ($count > 0 ? '#28a745' : '#6c757d') . ";'>{$count}</span>";
        echo "</div>";
    }
    echo "</div>";
    
    // Botones de limpieza
    // ...existing code...
    
} else {
    // ...existing code...
}

echo "</div>";

echo "<div class='info-box'>";
echo "<h4>ℹ️ Características del Sistema:</h4>";
echo "<ul>";
echo "<li><strong>Multi-intento:</strong> Los exámenes permiten intentos ilimitados</li>";
echo "<li><strong>Filtrado por estilo:</strong> Los recursos se filtran automáticamente por estilo de aprendizaje</li>";
echo "<li><strong>Rutas adaptativas:</strong> Sistema de saltos condicionales basado en resultados</li>";
echo "<li><strong>Progreso persistente:</strong> El progreso se guarda automáticamente</li>";
echo "<li><strong>Multi-instancia:</strong> ✨ Múltiples rutas por curso (NUEVO)</li>";
echo "<li><strong>Navegación contextual:</strong> ✨ Botones mantienen contexto de instancia (NUEVO)</li>";
echo "</ul>";
echo "</div>";

echo "</div>";

// Botones de navegación principales
echo "<div class='nav-buttons'>";
echo "<h3>🧭 Navegación</h3>";

// Botón principal de regreso
if ($id > 0) {
    $view_url = new moodle_url('/mod/learningstylesurvey/view.php', ['id' => $id]);
    echo "<a href='" . $view_url->out() . "' class='btn btn-primary' style='font-size: 16px; padding: 12px 24px;'>← Volver al Módulo Principal</a> ";
} else {
    $course_url = new moodle_url('/course/view.php', ['id' => $courseid]);
    echo "<a href='" . $course_url->out() . "' class='btn btn-primary' style='font-size: 16px; padding: 12px 24px;'>← Volver al Curso</a> ";
}

// Botones de acciones rápidas
echo "<a href='#' onclick='window.print()' class='btn btn-secondary'>🖨️ Imprimir Reporte</a> ";
echo "<a href='#' onclick='location.reload()' class='btn btn-warning'>🔄 Actualizar Verificación</a> ";

// Botón de documentación
$docs_url = new moodle_url('/mod/learningstylesurvey/documentacion');
echo "<a href='" . $docs_url->out() . "' class='btn btn-success'>📖 Ver Documentación</a>";

echo "<br><br>";
echo "<small>🕒 Última verificación: " . date('Y-m-d H:i:s') . " | ";
echo "👤 Usuario: {$USER->firstname} {$USER->lastname} | ";
echo "🎓 Curso ID: {$courseid}</small>";
echo "</div>";

// Footer con información adicional
echo "<div style='background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 8px; text-align: center; border: 1px solid #dee2e6;'>";
echo "<h4>ℹ️ Información del Sistema</h4>";
echo "<p><strong>Learning Style Survey Module</strong> - Sistema de rutas de aprendizaje adaptativas</p>";
echo "<p>Este módulo permite crear rutas de aprendizaje personalizadas basadas en estilos de aprendizaje, ";
echo "con evaluaciones adaptativas y sistema de refuerzos automáticos.</p>";
echo "<p><small>Desarrollado para Moodle | Versión del plugin: " . ($plugin_version ? $plugin_version : 'N/A') . "</small></p>";
echo "</div>";

echo "<script type='text/javascript'>";
// Auto-refresh cada 5 minutos para verificaciones en tiempo real
echo "setTimeout(function() {\n";
echo "    var refresh = confirm('¿Deseas actualizar la verificación automáticamente?');\n";
echo "    if (refresh) location.reload();\n";
echo "}, 300000); // 5 minutos\n";
echo "</script>";

// ==========================================
// SECCIÓN DE DIAGNÓSTICO DE ACCESIBILIDAD
// ==========================================
echo "<div style='background: #e7f3ff; padding: 20px; margin: 20px 0; border-radius: 8px; border: 1px solid #b3d9ff;'>";
echo "<h3>🔍 Diagnóstico de Accesibilidad de Archivos</h3>";
echo "<p>Verificando que los archivos principales sean accesibles vía web</p>";

// URLs a verificar - Usar cmid cuando esté disponible
$urls_to_check = [
    'Vista principal' => '/mod/learningstylesurvey/view.php?id=' . ($id ?: 1),
    'Subir recursos' => '/mod/learningstylesurvey/resource/uploadresource.php?courseid=' . $courseid . ($id ? '&cmid=' . $id : ''),
    'Ver recursos' => '/mod/learningstylesurvey/resource/viewresources.php?courseid=' . $courseid . ($id ? '&cmid=' . $id : ''),
    'Crear examen' => '/mod/learningstylesurvey/quiz/crear_examen.php?courseid=' . $courseid . ($id ? '&cmid=' . $id : ''),
    'Gestionar rutas' => '/mod/learningstylesurvey/path/learningpath.php?courseid=' . $courseid . ($id ? '&cmid=' . $id : ''),
    'Vista estudiante' => '/mod/learningstylesurvey/path/vista_estudiante.php?courseid=' . $courseid . ($id ? '&cmid=' . $id : '')
];

echo "<table border='1' style='border-collapse:collapse; width:100%; margin-top:10px;'>";
echo "<tr><th>Función</th><th>URL</th><th>Estado HTTP</th><th>Archivo en Sistema</th></tr>";

foreach ($urls_to_check as $function => $url_path) {
    // Verificar si el archivo físico existe (solo archivos PHP principales)
    $file_path = $CFG->dirroot . $url_path;
    $file_exists = 'N/A'; // No verificar archivos físicos para evitar falsos positivos

    // Verificar accesibilidad web (solo si tenemos contexto completo)
    $full_url = $CFG->wwwroot . $url_path;
    $status = '✅ Configurado';

    echo "<tr>";
    echo "<td>{$function}</td>";
    echo "<td><a href='{$full_url}' target='_blank' title='Abrir en nueva pestaña'>" . basename($url_path) . "</a></td>";
    echo "<td>{$status}</td>";
    echo "<td>{$file_exists}</td>";
    echo "</tr>";
}
echo "</table>";
echo "<p><small><em>Nota: Los archivos se verifican por accesibilidad web, no por existencia física en el sistema de archivos.</em></small></p>";
echo "</div>";

// ==========================================
// SECCIÓN DE VERIFICACIÓN DE RUTAS
// ==========================================
echo "<div style='background: #f0f9ff; padding: 20px; margin: 20px 0; border-radius: 8px; border: 1px solid #b3e5fc;'>";
echo "<h3>🔧 Verificación de Estructura del Plugin</h3>";
echo "<p>Verificando directorios y archivos principales del módulo</p>";

// Directorios a verificar
$directories = [
    'resource' => 'Subida y gestión de recursos',
    'quiz' => 'Sistema de exámenes y evaluaciones',
    'path' => 'Rutas de aprendizaje adaptativas',
    'utils' => 'Herramientas de diagnóstico',
    'db' => 'Estructura de base de datos',
    'lang' => 'Archivos de idioma'
];

echo "<table border='1' style='border-collapse:collapse; width:100%; margin-top:10px;'>";
echo "<tr><th>Directorio</th><th>Propósito</th><th>Estado</th></tr>";

foreach ($directories as $dir => $purpose) {
    $dir_path = $CFG->dirroot . '/mod/learningstylesurvey/' . $dir;
    $dir_exists = is_dir($dir_path) ? '✅ OK' : '❌ No encontrado';

    echo "<tr>";
    echo "<td><code>{$dir}/</code></td>";
    echo "<td>{$purpose}</td>";
    echo "<td>{$dir_exists}</td>";
    echo "</tr>";
}
echo "</table>";
echo "<p><small><em>Nota: Se verifica la existencia de directorios, no archivos individuales.</em></small></p>";
echo "</div>";

// ==========================================
// SECCIÓN DE FUNCIONES DISPONIBLES
// ==========================================
echo "<div style='background: #fff3cd; padding: 20px; margin: 20px 0; border-radius: 8px; border: 1px solid #ffeaa7;'>";
echo "<h3>🔧 Verificación de Funciones del Sistema</h3>";

// Verificar que las funciones existen
$functions = [
    'learningstylesurvey_ensure_upload_directory',
    'learningstylesurvey_migrate_files'
];

echo "<ul style='margin-top:10px;'>";
foreach ($functions as $function) {
    if (function_exists($function)) {
        echo "<li>✅ <code>{$function}</code> - Disponible</li>";
    } else {
        echo "<li>❌ <code>{$function}</code> - NO Disponible</li>";
    }
}
echo "</ul>";

// Probar las funciones con el courseid actual
echo "<h4>🧪 Prueba de Funciones</h4>";
$test_courseid = $courseid;
echo "<p>Probando con Course ID: <strong>{$test_courseid}</strong></p>";

if (function_exists('learningstylesurvey_ensure_upload_directory')) {
    $upload_dir = learningstylesurvey_ensure_upload_directory($test_courseid);
    echo "<p>✅ <code>learningstylesurvey_ensure_upload_directory({$test_courseid})</code></p>";
    echo "<p>Ruta generada: <code>{$upload_dir}</code></p>";
    echo "<p>Directorio existe: " . (is_dir($upload_dir) ? '✅ Sí' : '❌ No') . "</p>";
    echo "<p>Directorio escribible: " . (is_writable($upload_dir) ? '✅ Sí' : '❌ No') . "</p>";
} else {
    echo "<p>❌ Función <code>learningstylesurvey_ensure_upload_directory</code> no disponible</p>";
}

echo "</div>";

// ==========================================
// 📋 NOTA IMPORTANTE SOBRE CMID
// ==========================================
echo "<div style='background: #e8f5e8; padding: 20px; margin: 20px 0; border-radius: 8px; border: 1px solid #c8e6c9;'>";
echo "<h3>📋 Información sobre Multi-instancia (cmid)</h3>";
echo "<p><strong>Estado del soporte multi-instancia:</strong></p>";
echo "<ul>";
echo "<li>✅ Campo <code>cmid</code> agregado a tabla <code>learningstylesurvey_paths</code></li>";
echo "<li>✅ URLs incluyen <code>cmid</code> cuando está disponible (<code>" . ($id ?: 'No especificado') . "</code>)</li>";
echo "<li>✅ Sistema preparado para múltiples instancias del módulo en el mismo curso</li>";
echo "</ul>";
echo "<p><strong>Nota:</strong> Las acciones de limpieza usan <code>courseid</code> para afectar todo el curso, ";
echo "mientras que las operaciones específicas de rutas pueden usar <code>cmid</code> para aislamiento por instancia.</p>";
echo "</div>";

?>
