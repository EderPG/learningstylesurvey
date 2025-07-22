
<?php
require_once('../../config.php');
require_login();

$courseid = required_param('courseid', PARAM_INT);
$context = context_course::instance($courseid);
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/mod/learningstylesurvey/crear_paso_ruta.php', ['courseid' => $courseid]));
$PAGE->set_title('Crear Paso Ruta Informativa');
$PAGE->set_heading('Crear Paso en la Ruta Informativa');

echo $OUTPUT->header();
echo $OUTPUT->heading("Formulario para crear paso en la Ruta Informativa");

// Obtener archivos subidos previamente desde la base de datos
$resources = $DB->get_records('learningstylesurvey_files', ['courseid' => $courseid]);

echo '<form method="post" action="guardar_paso_ruta.php?courseid=' . $courseid . '">';
echo '<label>Nombre del paso:</label><br>';
echo '<input type="text" name="nombre" required><br><br>';

echo '<label>Seleccionar recurso ya subido:</label><br>';
echo '<select name="archivo" required>';
foreach ($resources as $resource) {
    $label = $resource->filename . ' (' . $resource->learningstyle . ')';
    echo '<option value="' . $resource->filename . '">' . $label . '</option>';
}
echo '</select><br><br>';

echo '<label>Instrucciones:</label><br>';
echo '<textarea name="instrucciones" rows="4" cols="50"></textarea><br><br>';

echo '<input type="hidden" name="courseid" value="' . $courseid . '">';
echo '<input type="submit" value="Guardar paso">';
echo '</form>';

echo $OUTPUT->footer();
?>
