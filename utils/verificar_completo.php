<?php
/**
 * Verificación completa de todas las rutas y enlaces en el proyecto
 */
require_once('../../../config.php');
require_login();

global $DB, $USER;

echo "<h1>🔍 Verificación Completa de Rutas y Enlaces</h1>";
echo "<p><strong>Usuario:</strong> {$USER->firstname} {$USER->lastname}</p>";
echo "<hr>";

// Función para verificar si un archivo existe
function check_file_exists($relative_path) {
    $full_path = dirname(__FILE__) . '/../' . $relative_path;
    return file_exists($full_path) ? '✅ Existe' : '❌ No existe';
}

// Verificar rutas críticas
echo "<h2>📂 Verificación de Archivos Críticos</h2>";
$critical_files = [
    'Archivo principal' => 'view.php',
    'Configuración' => 'lib.php',
    'Formulario' => 'mod_form.php',
    'Resultados' => 'results.php',
    'Encuesta' => 'surveyform.php'
];

echo "<table border='1' style='border-collapse:collapse;'>";
echo "<tr><th>Componente</th><th>Archivo</th><th>Estado</th></tr>";
foreach ($critical_files as $component => $file) {
    $status = check_file_exists($file);
    echo "<tr><td>{$component}</td><td>{$file}</td><td>{$status}</td></tr>";
}
echo "</table>";

// Verificar archivos en subdirectorios
echo "<h2>📁 Verificación de Subdirectorios</h2>";
$subdirs = [
    'resource' => ['uploadresource.php', 'viewresources.php', 'ver_recurso.php', 'temas.php'],
    'quiz' => ['crear_examen.php', 'guardar_examen.php', 'manage_quiz.php', 'responder_quiz.php'],
    'path' => ['learningpath.php', 'vista_estudiante.php', 'createsteproute.php', 'siguiente.php'],
    'debug' => ['debug_retry.php', 'debug_saltos.php'],
    'utils' => ['migrar_recursos.php', 'verificar_funcionalidades.php', 'prueba_final.php']
];

echo "<table border='1' style='border-collapse:collapse;'>";
echo "<tr><th>Directorio</th><th>Archivo</th><th>Estado</th></tr>";
foreach ($subdirs as $dir => $files) {
    foreach ($files as $file) {
        $relative_path = $dir . '/' . $file;
        $status = check_file_exists($relative_path);
        echo "<tr><td>{$dir}/</td><td>{$file}</td><td>{$status}</td></tr>";
    }
}
echo "</table>";

// Verificar rutas de inclusión
echo "<h2>🔗 Verificación de Rutas de Inclusión</h2>";
$include_paths = [
    'Archivos en raíz' => '../../config.php',
    'Archivos en resource/' => '../../../config.php',
    'Archivos en quiz/' => '../../../config.php',
    'Archivos en path/' => '../../../config.php',
    'Archivos en debug/' => '../../../config.php',
    'Archivos en utils/' => '../../../config.php'
];

echo "<table border='1' style='border-collapse:collapse;'>";
echo "<tr><th>Ubicación</th><th>Ruta Esperada</th><th>Archivo de Prueba</th></tr>";
foreach ($include_paths as $location => $expected_path) {
    // Verificar si el archivo config.php existe en la ruta esperada
    $test_path = dirname(__FILE__) . '/' . $expected_path;
    $config_exists = file_exists($test_path) ? '✅ Correcta' : '❌ Incorrecta';
    echo "<tr><td>{$location}</td><td>{$expected_path}</td><td>{$config_exists}</td></tr>";
}
echo "</table>";

// Verificar enlaces comunes
echo "<h2>🔗 Verificación de Enlaces Comunes</h2>";
$common_links = [
    'Vista principal' => 'view.php',
    'Subir recursos' => 'resource/uploadresource.php',
    'Ver recursos' => 'resource/viewresources.php',
    'Crear examen' => 'quiz/crear_examen.php',
    'Gestionar rutas' => 'path/learningpath.php',
    'Vista estudiante' => 'path/vista_estudiante.php'
];

echo "<table border='1' style='border-collapse:collapse;'>";
echo "<tr><th>Función</th><th>Ruta</th><th>Estado</th></tr>";
foreach ($common_links as $function => $path) {
    $status = check_file_exists($path);
    echo "<tr><td>{$function}</td><td>{$path}</td><td>{$status}</td></tr>";
}
echo "</table>";

// Diagnóstico de problemas
echo "<h2>🔧 Diagnóstico de Problemas</h2>";
$problems = [];

if (!file_exists(dirname(__FILE__) . '/../config.php')) {
    $problems[] = "❌ El archivo config.php no se encuentra en la ubicación esperada";
}

if (!file_exists(dirname(__FILE__) . '/../lib.php')) {
    $problems[] = "❌ El archivo lib.php no existe (funciones críticas)";
}

if (!file_exists(dirname(__FILE__) . '/../view.php')) {
    $problems[] = "❌ El archivo view.php no existe (punto de entrada principal)";
}

if (empty($problems)) {
    echo "<div style='background:#d4edda; padding:15px; border:1px solid #c3e6cb; border-radius:5px;'>";
    echo "<h3>✅ No se encontraron problemas críticos</h3>";
    echo "<p>Todas las rutas y archivos principales están en orden.</p>";
    echo "</div>";
} else {
    echo "<div style='background:#f8d7da; padding:15px; border:1px solid #f5c6cb; border-radius:5px;'>";
    echo "<h3>❌ Se encontraron problemas:</h3>";
    echo "<ul>";
    foreach ($problems as $problem) {
        echo "<li>{$problem}</li>";
    }
    echo "</ul>";
    echo "</div>";
}

echo "<hr>";
echo "<p style='font-size:12px; color:#666;'>Verificación ejecutada el " . date('Y-m-d H:i:s') . "</p>";
?>
