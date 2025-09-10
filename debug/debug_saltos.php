<?php
require_once('../../../config.php');
require_login();

global $DB;

$courseid = optional_param('courseid', 0, PARAM_INT);
$pathid = optional_param('pathid', 0, PARAM_INT);

// Si no se proporcionan parámetros, mostrar ayuda
if (!$courseid) {
    echo "<h2>🔍 Debug: Análisis de Saltos y Refuerzos</h2>";
    echo "<div style='background:#fff3cd; padding:15px; border:1px solid #ffeaa7; border-radius:5px; margin:10px 0;'>";
    echo "<h3>📋 Instrucciones de Uso</h3>";
    echo "<p>Este script requiere los parámetros <code>courseid</code> y <code>pathid</code></p>";
    echo "<p><strong>Formato:</strong> <code>debug_saltos.php?courseid=X&pathid=Y</code></p>";
    echo "</div>";
    
    echo "<h3>📚 Cursos disponibles:</h3>";
    
    // Intentar obtener cursos de diferentes maneras según las tablas disponibles
    $courses = [];
    
    // Opción 1: Desde course_modules (más confiable)
    try {
        $courses = $DB->get_records_sql("
            SELECT DISTINCT c.id, c.fullname 
            FROM {course} c 
            JOIN {course_modules} cm ON cm.course = c.id
            JOIN {modules} m ON m.id = cm.module AND m.name = 'learningstylesurvey'
            WHERE c.id > 1
            ORDER BY c.fullname
        ");
        
        if (empty($courses)) {
            // Opción 2: Desde la instancia principal del módulo
            $courses = $DB->get_records_sql("
                SELECT DISTINCT c.id, c.fullname 
                FROM {course} c 
                JOIN {learningstylesurvey} ls ON ls.course IS NULL OR ls.course = c.id
                WHERE c.id > 1
                ORDER BY c.fullname
            ");
        }
        
        if (empty($courses)) {
            // Opción 3: Desde rutas existentes
            $courses = $DB->get_records_sql("
                SELECT DISTINCT c.id, c.fullname 
                FROM {course} c 
                JOIN {learningstylesurvey_paths} lp ON lp.courseid = c.id
                ORDER BY c.fullname
            ");
        }
        
    } catch (Exception $e) {
        echo "<div style='color:red;'>Error al obtener cursos: " . $e->getMessage() . "</div>";
        
        // Última opción: Mostrar todos los cursos disponibles
        try {
            $courses = $DB->get_records_sql("
                SELECT id, fullname 
                FROM {course} 
                WHERE id > 1 
                ORDER BY fullname
            ");
            echo "<div style='color:blue;'>ℹ️ Mostrando todos los cursos disponibles (puede que no tengan el módulo instalado):</div>";
        } catch (Exception $e2) {
            echo "<div style='color:red;'>Error crítico: " . $e2->getMessage() . "</div>";
        }
    }
    
    if (empty($courses)) {
        echo "<div style='color:orange;'>⚠️ No se encontraron cursos. Verifica que:</div>";
        echo "<ul>";
        echo "<li>El módulo esté instalado en algún curso</li>";
        echo "<li>Hayas agregado una instancia del módulo a un curso</li>";
        echo "<li>La base de datos esté funcionando correctamente</li>";
        echo "</ul>";
        
        // Mostrar información de debug sobre las tablas disponibles
        echo "<h4>🔧 Información de Debug:</h4>";
        echo "<div style='background:#f8f9fa; padding:10px; border:1px solid #dee2e6; border-radius:5px;'>";
        
        // Verificar qué tablas existen
        $tables_to_check = [
            'learningstylesurvey',
            'learningstylesurvey_paths', 
            'learningstylesurvey_temas',
            'course_modules'
        ];
        
        echo "<p><strong>Estado de tablas:</strong></p><ul>";
        foreach ($tables_to_check as $table) {
            $exists = $DB->get_manager()->table_exists($table);
            $count = $exists ? $DB->count_records($table) : 0;
            $status = $exists ? "✅ Existe ({$count} registros)" : "❌ No existe";
            echo "<li>{$table}: {$status}</li>";
        }
        echo "</ul>";
        
        // Mostrar algunos registros de ejemplo si existen
        if ($DB->get_manager()->table_exists('learningstylesurvey_paths')) {
            $sample_paths = $DB->get_records('learningstylesurvey_paths', null, '', '*', 0, 3);
            if (!empty($sample_paths)) {
                echo "<p><strong>Rutas de ejemplo:</strong></p><ul>";
                foreach ($sample_paths as $path) {
                    echo "<li>ID: {$path->id}, Curso: {$path->courseid}, Nombre: " . format_string($path->name) . "</li>";
                }
                echo "</ul>";
            }
        }
        echo "</div>";
    } else {
        echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
        echo "<tr><th>Course ID</th><th>Nombre del Curso</th><th>Acción</th></tr>";
        foreach ($courses as $course) {
            echo "<tr>";
            echo "<td>{$course->id}</td>";
            echo "<td>" . format_string($course->fullname) . "</td>";
            echo "<td><a href='?courseid={$course->id}'>Ver rutas de este curso</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Agregar formulario de acceso directo
    echo "<hr>";
    echo "<div style='background:#e7f3ff; padding:15px; border:1px solid #bee5eb; border-radius:5px; margin:20px 0;'>";
    echo "<h4>🚀 Acceso Directo</h4>";
    echo "<p>Si conoces el ID del curso, puedes ir directamente:</p>";
    echo "<form method='get' style='display:inline;'>";
    echo "<input type='number' name='courseid' placeholder='Course ID' required style='padding:5px; margin:5px;'>";
    echo "<input type='submit' value='Ir al curso' style='padding:5px 10px; margin:5px; background:#007bff; color:white; border:none; border-radius:3px;'>";
    echo "</form>";
    echo "</div>";
    exit;
}

if (!$pathid) {
    echo "<h2>🔍 Debug: Rutas del Curso {$courseid}</h2>";
    echo "<div style='background:#fff3cd; padding:15px; border:1px solid #ffeaa7; border-radius:5px; margin:10px 0;'>";
    echo "<p>Selecciona una ruta para analizar:</p>";
    echo "</div>";
    
    $paths = $DB->get_records('learningstylesurvey_paths', ['courseid' => $courseid]);
    if (empty($paths)) {
        echo "<p style='color:red;'>No se encontraron rutas en este curso</p>";
        echo "<p><a href='?'>← Volver a seleccionar curso</a></p>";
    } else {
        echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
        echo "<tr><th>Path ID</th><th>Nombre de la Ruta</th><th>Acción</th></tr>";
        foreach ($paths as $path) {
            echo "<tr>";
            echo "<td>{$path->id}</td>";
            echo "<td>" . format_string($path->name) . "</td>";
            echo "<td><a href='?courseid={$courseid}&pathid={$path->id}'>Analizar esta ruta</a></td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<br><p><a href='?'>← Volver a seleccionar curso</a></p>";
    }
    exit;
}

echo "<h2>🔍 Debug: Análisis de Saltos y Refuerzos</h2>";
echo "<p><strong>Path ID:</strong> {$pathid}</p>";

// 1. Estado de la tabla learningstylesurvey_path_temas
echo "<h3>📋 Tabla: learningstylesurvey_path_temas</h3>";
$path_temas = $DB->get_records('learningstylesurvey_path_temas', ['pathid' => $pathid], 'orden ASC');
if (empty($path_temas)) {
    echo "<div style='color:red;'>❌ No hay registros en learningstylesurvey_path_temas para pathid = {$pathid}</div>";
} else {
    echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
    echo "<tr><th>ID</th><th>PathID</th><th>TemaID</th><th>Orden</th><th>IsRefuerzo</th><th>Nombre del Tema</th></tr>";
    foreach ($path_temas as $pt) {
        $tema = $DB->get_record('learningstylesurvey_temas', ['id' => $pt->temaid]);
        $tema_nombre = $tema ? $tema->tema : 'No encontrado';
        $refuerzo_status = $pt->isrefuerzo ? '✅ Sí' : '❌ No';
        echo "<tr>";
        echo "<td>{$pt->id}</td>";
        echo "<td>{$pt->pathid}</td>";
        echo "<td>{$pt->temaid}</td>";
        echo "<td>{$pt->orden}</td>";
        echo "<td>{$refuerzo_status}</td>";
        echo "<td>{$tema_nombre}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 2. Estado de la tabla learningpath_steps
echo "<h3>📋 Tabla: learningpath_steps</h3>";
$steps = $DB->get_records('learningpath_steps', ['pathid' => $pathid], 'stepnumber ASC');
if (empty($steps)) {
    echo "<div style='color:red;'>❌ No hay registros en learningpath_steps para pathid = {$pathid}</div>";
} else {
    echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
    echo "<tr><th>ID</th><th>PathID</th><th>Step#</th><th>ResourceID</th><th>IsTest</th><th>PassRedirect</th><th>FailRedirect</th><th>Nombre/Recurso</th></tr>";
    foreach ($steps as $step) {
        if ($step->istest) {
            $quiz = $DB->get_record('learningstylesurvey_quizzes', ['id' => $step->resourceid]);
            $nombre = $quiz ? $quiz->name : 'Quiz no encontrado';
            $tipo = 'EXAMEN';
        } else {
            $resource = $DB->get_record('learningstylesurvey_resources', ['id' => $step->resourceid]);
            $nombre = $resource ? $resource->name : 'Recurso no encontrado';
            $tipo = 'RECURSO';
        }
        
        $pass_tema = '';
        $fail_tema = '';
        if ($step->passredirect > 0) {
            $tema_pass = $DB->get_record('learningstylesurvey_temas', ['id' => $step->passredirect]);
            $pass_tema = $tema_pass ? $tema_pass->tema : "Tema {$step->passredirect} no encontrado";
        }
        if ($step->failredirect > 0) {
            $tema_fail = $DB->get_record('learningstylesurvey_temas', ['id' => $step->failredirect]);
            $fail_tema = $tema_fail ? $tema_fail->tema : "Tema {$step->failredirect} no encontrado";
        }
        
        echo "<tr>";
        echo "<td>{$step->id}</td>";
        echo "<td>{$step->pathid}</td>";
        echo "<td>{$step->stepnumber}</td>";
        echo "<td>{$step->resourceid}</td>";
        echo "<td>" . ($step->istest ? '✅ Sí' : '❌ No') . "</td>";
        echo "<td>{$step->passredirect}" . ($pass_tema ? " ({$pass_tema})" : '') . "</td>";
        echo "<td>{$step->failredirect}" . ($fail_tema ? " ({$fail_tema})" : '') . "</td>";
        echo "<td>[{$tipo}] {$nombre}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 3. Verificar integridad de datos
echo "<h3>🔧 Verificación de Integridad</h3>";

// Verificar si los saltos apuntan a temas válidos
$problemas = [];
foreach ($steps as $step) {
    if ($step->istest) {
        if ($step->passredirect > 0) {
            $tema_exists = $DB->record_exists('learningstylesurvey_temas', ['id' => $step->passredirect]);
            if (!$tema_exists) {
                $problemas[] = "❌ Paso {$step->id}: PassRedirect apunta a tema inexistente ({$step->passredirect})";
            }
        }
        if ($step->failredirect > 0) {
            $tema_exists = $DB->record_exists('learningstylesurvey_temas', ['id' => $step->failredirect]);
            if (!$tema_exists) {
                $problemas[] = "❌ Paso {$step->id}: FailRedirect apunta a tema inexistente ({$step->failredirect})";
            }
        }
    }
}

if (empty($problemas)) {
    echo "<div style='color:green;'>✅ No se encontraron problemas de integridad</div>";
} else {
    foreach ($problemas as $problema) {
        echo "<div style='color:red;'>{$problema}</div>";
    }
}

// 4. Mostrar qué datos deberían aparecer en la vista organizar_ruta
echo "<h3>📊 Datos que deberían aparecer en organizar_ruta.php</h3>";
echo "<h4>Temas marcados como refuerzo:</h4>";
$temas_refuerzo = array_filter($path_temas, function($pt) { return $pt->isrefuerzo; });
if (empty($temas_refuerzo)) {
    echo "<div style='color:orange;'>⚠️ No hay temas marcados como refuerzo</div>";
} else {
    foreach ($temas_refuerzo as $tr) {
        $tema = $DB->get_record('learningstylesurvey_temas', ['id' => $tr->temaid]);
        echo "<div style='color:green;'>✅ {$tema->tema} (ID: {$tr->temaid})</div>";
    }
}

echo "<h4>Exámenes con saltos configurados:</h4>";
$examenes_con_saltos = array_filter($steps, function($s) { return $s->istest && ($s->passredirect > 0 || $s->failredirect > 0); });
if (empty($examenes_con_saltos)) {
    echo "<div style='color:orange;'>⚠️ No hay exámenes con saltos configurados</div>";
} else {
    foreach ($examenes_con_saltos as $ex) {
        $quiz = $DB->get_record('learningstylesurvey_quizzes', ['id' => $ex->resourceid]);
        echo "<div style='color:green;'>✅ {$quiz->name}:</div>";
        if ($ex->passredirect > 0) {
            $tema_pass = $DB->get_record('learningstylesurvey_temas', ['id' => $ex->passredirect]);
            echo "<div style='margin-left:20px;'>- Si aprueba → {$tema_pass->tema}</div>";
        }
        if ($ex->failredirect > 0) {
            $tema_fail = $DB->get_record('learningstylesurvey_temas', ['id' => $ex->failredirect]);
            echo "<div style='margin-left:20px;'>- Si reprueba → {$tema_fail->tema}</div>";
        }
    }
}

echo "<br><hr>";
echo "<p><a href='../path/organizar_ruta.php?courseid={$courseid}&pathid={$pathid}'>🔗 Ir a Organizar Ruta</a></p>";
echo "<p><a href='../utils/verificar_funcionalidades.php?courseid={$courseid}'>🔗 Ir a Verificar Funcionalidades</a></p>";
?>
