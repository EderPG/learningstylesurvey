
<?php
require_once('../../config.php');

// Obtener el nombre del archivo por parámetro
$filename = required_param('file', PARAM_FILE);
$filepath = __DIR__ . '/uploads/' . $filename;

// Verifica que el archivo exista físicamente
if (!file_exists($filepath)) {
    print_error('Archivo no encontrado: ' . $filename);
}

// Forzar la descarga
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
header('Content-Length: ' . filesize($filepath));
flush();
readfile($filepath);
exit;
