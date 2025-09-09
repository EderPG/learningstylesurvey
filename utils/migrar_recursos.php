<?php
/**
 * Script de migración para actualizar recursos existentes
 * Ejecutar DESPUÉS del upgrade de base de datos
 */
require_once('../../../config.php');
require_login();

global $DB, $USER;

// Solo administradores pueden ejecutar esto
if (!is_siteadmin()) {
    die('Solo administradores pueden ejecutar este script');
}

echo "<h2>🔄 Migración de Recursos Existentes</h2>";

// Buscar recursos sin userid (valor 0 o NULL)
$recursos_sin_usuario = $DB->get_records_sql("
    SELECT * FROM {learningstylesurvey_resources} 
    WHERE userid = 0 OR userid IS NULL
");

echo "<p>Recursos encontrados sin usuario asignado: <strong>" . count($recursos_sin_usuario) . "</strong></p>";

if (!empty($recursos_sin_usuario)) {
    echo "<div style='background:#fff3cd; padding:10px; border:1px solid #ffeaa7; border-radius:5px; margin:10px 0;'>";
    echo "<h4>⚠️ Acción requerida:</h4>";
    echo "<p>Hay recursos que no tienen usuario asignado. Estas son las opciones:</p>";
    echo "<ol>";
    echo "<li><strong>Asignar al primer admin:</strong> Todos los recursos se asignarán al administrador principal</li>";
    echo "<li><strong>Eliminar huérfanos:</strong> Se eliminarán los recursos sin dueño (no recomendado)</li>";
    echo "<li><strong>Manual:</strong> Asignar manualmente cada recurso</li>";
    echo "</ol>";
    
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'assign_admin') {
            // Obtener el primer administrador
            $admin = $DB->get_record_sql("
                SELECT u.* FROM {user} u 
                JOIN {role_assignments} ra ON ra.userid = u.id 
                JOIN {role} r ON r.id = ra.roleid 
                WHERE r.shortname = 'manager' OR r.shortname = 'admin'
                ORDER BY u.id ASC LIMIT 1
            ");
            
            if ($admin) {
                $updated = 0;
                foreach ($recursos_sin_usuario as $recurso) {
                    $recurso->userid = $admin->id;
                    $DB->update_record('learningstylesurvey_resources', $recurso);
                    $updated++;
                }
                echo "<p style='color:green;'>✅ Asignados $updated recursos al administrador.</p>";
            } else {
                echo "<p style='color:red;'>❌ No se encontró un administrador válido.</p>";
            }
        }
    } else {
        echo "<form method='post'>";
        echo "<button type='submit' name='action' value='assign_admin' class='btn btn-primary'>Asignar al primer admin</button>";
        echo "</form>";
    }
    echo "</div>";
}

// Migrar archivos físicos
echo "<h3>📁 Migración de Archivos Físicos</h3>";

$courses = $DB->get_records('course', [], 'id ASC');
$migrated_files = 0;
$errors = [];

foreach ($courses as $course) {
    $old_dir = $CFG->dirroot . '/mod/learningstylesurvey/uploads/';
    $new_dir = $CFG->dataroot . '/learningstylesurvey/' . $course->id . '/';
    
    if (is_dir($old_dir)) {
        // Crear directorio nuevo si no existe
        if (!is_dir($new_dir)) {
            mkdir($new_dir, 0755, true);
        }
        
        // Obtener todos los archivos del directorio antiguo
        $files = glob($old_dir . '*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $filename = basename($file);
                $new_path = $new_dir . $filename;
                
                // Verificar si el archivo ya existe en el nuevo directorio
                if (!file_exists($new_path)) {
                    if (copy($file, $new_path)) {
                        $migrated_files++;
                        echo "<p style='color:green;'>✅ Migrado: $filename → curso {$course->id}</p>";
                    } else {
                        $errors[] = "Error copiando $filename";
                    }
                }
            }
        }
    }
}

echo "<h3>📊 Resultados de la Migración</h3>";
echo "<p><strong>Archivos migrados:</strong> $migrated_files</p>";

if (!empty($errors)) {
    echo "<p style='color:red;'><strong>Errores:</strong></p>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul>";
}

echo "<div style='background:#d4edda; padding:15px; border:1px solid #c3e6cb; border-radius:5px; margin:20px 0;'>";
echo "<h4>✅ Migración Completada</h4>";
echo "<p>Los archivos han sido migrados al nuevo sistema de archivos de Moodle.</p>";
echo "<p><strong>Nota:</strong> Puedes eliminar manualmente el directorio antiguo <code>mod/learningstylesurvey/uploads/</code> después de verificar que todo funciona correctamente.</p>";
echo "</div>";

// Buscar recursos sin userid (valor 0 o NULL)
$recursos_sin_usuario = $DB->get_records_sql("
    SELECT * FROM {learningstylesurvey_resources} 
    WHERE userid = 0 OR userid IS NULL
");

echo "<p>Recursos encontrados sin usuario asignado: <strong>" . count($recursos_sin_usuario) . "</strong></p>";

if (!empty($recursos_sin_usuario)) {
    echo "<div style='background:#fff3cd; padding:10px; border:1px solid #ffeaa7; border-radius:5px; margin:10px 0;'>";
    echo "<h4>⚠️ Acción requerida:</h4>";
    echo "<p>Hay recursos que no tienen usuario asignado. Estas son las opciones:</p>";
    echo "<ol>";
    echo "<li><strong>Asignar al primer admin:</strong> Todos los recursos se asignarán al administrador principal</li>";
    echo "<li><strong>Eliminar huérfanos:</strong> Se eliminarán los recursos sin dueño (no recomendado)</li>";
    echo "<li><strong>Manual:</strong> Asignar manualmente cada recurso</li>";
    echo "</ol>";
    
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'assign_admin') {
            // Obtener el primer administrador
            $admin = $DB->get_record_sql("
                SELECT u.* FROM {user} u 
                JOIN {role_assignments} ra ON ra.userid = u.id 
                JOIN {role} r ON r.id = ra.roleid 
                WHERE r.shortname = 'manager' OR r.shortname = 'admin'
                ORDER BY u.id ASC LIMIT 1
            ");
            
            if ($admin) {
                $updated = 0;
                foreach ($recursos_sin_usuario as $recurso) {
                    $recurso->userid = $admin->id;
                    if ($DB->update_record('learningstylesurvey_resources', $recurso)) {
                        $updated++;
                    }
                }
                echo "<div style='color:green;'>✅ Se actualizaron {$updated} recursos asignándolos a: {$admin->firstname} {$admin->lastname}</div>";
            } else {
                echo "<div style='color:red;'>❌ No se encontró ningún administrador</div>";
            }
        }
    } else {
        echo "<form method='post'>";
        echo "<button type='submit' name='action' value='assign_admin' style='background:#007bff; color:white; padding:10px 15px; border:none; border-radius:5px; margin:5px;'>Asignar al Admin Principal</button>";
        echo "</form>";
        
        echo "<h4>📋 Recursos huérfanos encontrados:</h4>";
        echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Archivo</th><th>Curso</th><th>Estilo</th></tr>";
        foreach ($recursos_sin_usuario as $recurso) {
            echo "<tr>";
            echo "<td>{$recurso->id}</td>";
            echo "<td>" . format_string($recurso->name) . "</td>";
            echo "<td>{$recurso->filename}</td>";
            echo "<td>{$recurso->courseid}</td>";
            echo "<td>{$recurso->style}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";
} else {
    echo "<div style='color:green; background:#d4edda; padding:10px; border:1px solid #c3e6cb; border-radius:5px;'>";
    echo "✅ Todos los recursos tienen usuario asignado. No se requiere migración.";
    echo "</div>";
}

echo "<p><a href='verificar_funcionalidades.php?courseid=1'>← Volver a Verificar Funcionalidades</a></p>";
?>
