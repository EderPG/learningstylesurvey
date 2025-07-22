
<?php
require_once('../../config.php');
require_login();

$courseid = required_param('courseid', PARAM_INT);
$context = context_course::instance($courseid);
$PAGE->set_url(new moodle_url('/mod/learningstylesurvey/ver_ruta_informativa.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_heading("Ruta Informativa");

global $DB, $OUTPUT, $PAGE;

$pasos = $DB->get_records('learningstylesurvey_inforoute', ['courseid' => $courseid], 'steporder ASC');

echo $OUTPUT->header();
echo "<h2>Ruta Informativa (Vista Alumno)</h2>";

$pasoactual = optional_param('paso', 1, PARAM_INT);
$total = count($pasos);
$pasos_array = array_values($pasos);

if ($pasoactual <= $total) {
    $actual = $pasos_array[$pasoactual - 1];
    echo "<b>Paso $pasoactual de $total</b><br><br>";
    echo "<i>{$actual->instructions}</i><br><br>";
    echo "<iframe src='uploads/{$actual->filename}' width='100%' height='500px'></iframe><br><br>";

    if ($pasoactual < $total) {
        $siguiente = $pasoactual + 1;
        echo "<a href='ver_ruta_informativa.php?courseid=$courseid&paso=$siguiente'><button>Siguiente</button></a>";
    } else {
        echo "<b>Has llegado al final de la ruta.</b>";
    }
} else {
    echo "<b>No hay recursos en esta ruta aún.</b>";
}

echo $OUTPUT->footer();
?>
