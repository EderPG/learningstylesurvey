<?php
require_once("../../config.php");
require_login();

$filename = required_param('filename', PARAM_FILE);  // Ej: documento.pdf
$courseid = required_param('courseid', PARAM_INT);

$context = context_course::instance($courseid);
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/mod/learningstylesurvey/ver_recurso.php', ['filename' => $filename, 'courseid' => $courseid]));
$PAGE->set_title("Ver recurso");
$PAGE->set_heading("Recurso");

echo $OUTPUT->header();

$filepath = "/mod/learningstylesurvey/uploads/" . $filename;
$fileurl = new moodle_url($filepath);

// Mostrar vista previa interna (solo si es imagen o PDF)
if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $filename)) {
    echo "<img src='{$fileurl}' style='max-width:100%; margin: 20px auto; display: block;'>";
} elseif (preg_match('/\.pdf$/i', $filename)) {
    echo "<embed src='{$fileurl}' width='100%' height='700px' type='application/pdf'>";
} elseif (preg_match('/\.(mp4|webm|ogg)$/i', $filename))
    echo "<video width='100%' height='auto' controls style='margin: 20px auto; display:block;'>
            <source src='{$fileurl}' type='video/mp4'>
            Tu navegador no soporta la reproducción de video.
          </video>";
    else {
    echo "<p><a href='{$fileurl}' download>Descargar recurso</a></p>";
}

echo "<div style='margin-top:20px; text-align:center;'>";
$volver_url = new moodle_url('/mod/learningstylesurvey/vista_estudiante.php', ['courseid' => $courseid]);
echo "<a href='" . $volver_url->out() . "' class='btn btn-secondary'>Volver</a>";
echo "</div>";

echo $OUTPUT->footer();
