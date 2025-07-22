
<?php
require_once('../../config.php');
require_login();

global $OUTPUT, $PAGE;

$courseid = required_param('courseid', PARAM_INT);
$PAGE->set_url('/mod/learningstylesurvey/index.php', array('courseid' => $courseid));
$PAGE->set_title('Ruta de Aprendizaje');
$PAGE->set_heading('Ruta de Aprendizaje');

echo $OUTPUT->header();
echo html_writer::tag('h2', 'Ruta de Aprendizaje');

echo "<ul>";
echo "<li><a href='crear_ruta_informativa.php?courseid={$courseid}'>Crear Ruta de Aprendizaje</a></li>";
echo "<li><a href='ordenar_ruta.php?courseid={$courseid}'>Editar Ruta de Aprendizaje</a></li>";
echo "<li><a href='eliminar_ruta.php?courseid={$courseid}'>Eliminar Ruta de Aprendizaje</a></li>";
echo "</ul>";

echo "<a href='viewresources.php?courseid={$courseid}' class='btn btn-dark'>Regresar al Menú Anterior</a>";

echo $OUTPUT->footer();
