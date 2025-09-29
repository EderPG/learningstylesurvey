<?php
require_once("../../../config.php");
require_login();

$filename = required_param('filename', PARAM_FILE);  // Ej: documento.pdf
$courseid = required_param('courseid', PARAM_INT);

// Verificar si se solicita servir el archivo directamente (sin HTML)
$serve = optional_param('serve', 0, PARAM_INT);

if ($serve) {
    // Servir el archivo directamente
    $filepath = $CFG->dataroot . "/learningstylesurvey/" . $courseid . "/" . $filename;
    
    if (!file_exists($filepath)) {
        header('HTTP/1.0 404 Not Found');
        die('Archivo no encontrado');
    }
    
    // Verificar permisos de acceso al archivo
    $resource = $DB->get_record('learningstylesurvey_resources', [
        'filename' => $filename,
        'courseid' => $courseid
    ]);
    
    if (!$resource) {
        header('HTTP/1.0 404 Not Found');
        die('Recurso no encontrado');
    }
    
    // Determinar tipo MIME
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $mime_types = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'avi' => 'video/x-msvideo',
        'mov' => 'video/quicktime',
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'ogg' => 'audio/ogg',
        'txt' => 'text/plain',
        'html' => 'text/html',
        'htm' => 'text/html',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation'
    ];
    
    $mime_type = isset($mime_types[$ext]) ? $mime_types[$ext] : 'application/octet-stream';
    
    // Servir el archivo
    header('Content-Type: ' . $mime_type);
    header('Content-Length: ' . filesize($filepath));
    header('Content-Disposition: inline; filename="' . basename($filename) . '"');
    header('Content-Transfer-Encoding: binary');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    header('X-Content-Type-Options: nosniff');
    
    // Limpiar cualquier salida previa
    ob_clean();
    flush();
    
    readfile($filepath);
    exit;
}

$context = context_course::instance($courseid);
$PAGE->set_context($context);

// Para PDFs y otros archivos, servir directamente sin wrapper HTML
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'mp3', 'wav'])) {
    // Redireccionar directamente al archivo servido
    $fileurl = new moodle_url("/mod/learningstylesurvey/resource/ver_recurso.php", [
        'filename' => $filename, 
        'courseid' => $courseid,
        'serve' => 1
    ]);
    redirect($fileurl);
    exit;
}

$PAGE->set_url(new moodle_url('/mod/learningstylesurvey/resource/ver_recurso.php', ['filename' => $filename, 'courseid' => $courseid]));
$PAGE->set_title("Ver: " . basename($filename));
$PAGE->set_heading("Recurso: " . basename($filename));

echo $OUTPUT->header();

$filepath = $CFG->dataroot . "/learningstylesurvey/" . $courseid . "/" . $filename;
$fileurl = new moodle_url("/mod/learningstylesurvey/resource/ver_recurso.php", [
    'filename' => $filename, 
    'courseid' => $courseid,
    'serve' => 1
]);
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

// Verificar si el archivo existe físicamente
if (!file_exists($filepath)) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ Archivo no encontrado</h4>";
    echo "<p><strong>Archivo solicitado:</strong> " . htmlspecialchars($filename) . "</p>";
    echo "<p>El archivo puede haber sido movido o eliminado del servidor.</p>";
    echo "</div>";
} else {
    // Mostrar vista previa según el tipo de archivo
    if (in_array($ext, ['jpg','jpeg','png','gif','webp','svg'])) {
        // Imágenes
        echo "<img src='{$fileurl}' style='max-width:100%; margin: 20px auto; display: block; border: 1px solid #ddd; border-radius: 8px;'>";
        
    } elseif ($ext === 'pdf') {
        // PDFs
        echo "<embed src='{$fileurl}' width='100%' height='700px' type='application/pdf' style='border-radius: 8px;'>";
        
    } elseif (in_array($ext, ['mp4','webm','ogg','avi','mov'])) {
        // Videos
        echo "<video width='100%' height='auto' controls style='margin: 20px auto; display:block; border-radius: 8px;'>";
        echo "<source src='{$fileurl}' type='video/{$ext}'>";
        echo "Tu navegador no soporta la reproducción de video.";
        echo "</video>";
        
    } elseif (in_array($ext, ['mp3','wav','ogg'])) {
        // Audio
        echo "<audio controls style='width:100%; margin: 20px auto; display:block;'>";
        echo "<source src='{$fileurl}' type='audio/{$ext}'>";
        echo "Tu navegador no soporta audio HTML5.";
        echo "</audio>";
        
    } elseif ($ext === 'txt') {
        // Archivos de texto - mostrar contenido
        $content = file_get_contents($filepath);
        if ($content !== false) {
            echo "<div style='background:#f8f9fa; padding:20px; margin:20px; border:1px solid #dee2e6; border-radius:8px; font-family:monospace; white-space:pre-wrap; max-height:500px; overflow-y:auto;'>";
            echo htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
            echo "</div>";
        } else {
            echo "<div class='alert alert-warning'>No se pudo leer el contenido del archivo de texto.</div>";
        }
        
    } elseif (in_array($ext, ['doc','docx','xls','xlsx','ppt','pptx'])) {
        // Archivos de Microsoft Office - usar Office Online Viewer
        $encoded_url = urlencode($fileurl->out(false));
        echo "<iframe src='https://view.officeapps.live.com/op/embed.aspx?src={$encoded_url}' style='width:100%; height:700px; border:none; border-radius: 8px;'></iframe>";
        echo "<p style='text-align:center; margin-top:10px;'><small>📝 <strong>Nota:</strong> Si el visor no funciona, <a href='{$fileurl}' target='_blank'>descarga el archivo</a> para abrirlo localmente.</small></p>";
        
    } elseif (in_array($ext, ['html','htm'])) {
        // Archivos HTML
        echo "<iframe src='{$fileurl}' style='width:100%; height:600px; border:1px solid #ddd; border-radius: 8px;'></iframe>";
        
    } else {
        // Otros tipos de archivo - enlace de descarga mejorado
        $filesize = human_filesize(filesize($filepath));
        echo "<div style='background:#f8f9fa; padding:30px; margin:20px; border:1px solid #dee2e6; border-radius:8px; text-align:center;'>";
        echo "<h4>📁 " . htmlspecialchars($filename) . "</h4>";
        echo "<p>📊 <strong>Tamaño:</strong> {$filesize}</p>";
        echo "<p><a href='{$fileurl}' download class='btn btn-primary' style='background:#007bff; color:white; padding:12px 24px; text-decoration:none; border-radius:5px; font-size:16px;'>📥 Descargar archivo</a></p>";
        echo "</div>";
    }
}

// Función auxiliar para formatear tamaño de archivo
function human_filesize($size, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    return round($size, $precision) . ' ' . $units[$i];
}

echo "<div style='margin-top:20px; text-align:center;'>";
$volver_url = new moodle_url('/mod/learningstylesurvey/path/vista_estudiante.php', ['courseid' => $courseid]);
echo "<a href='" . $volver_url->out() . "' class='btn btn-secondary'>Volver</a>";
echo "</div>";

echo $OUTPUT->footer();
