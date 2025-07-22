<?php
require_once('../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$context = context_course::instance($courseid);
require_login($courseid);

$PAGE->set_url('/mod/learningstylesurvey/createeducationalresource.php', array('courseid' => $courseid));
$PAGE->set_context($context);
$PAGE->set_title('Crear Recurso Educativo');
$PAGE->set_heading('Crear Recurso Educativo');

echo $OUTPUT->header();
echo $OUTPUT->heading('Formulario específico para crear recurso educativo');

echo '<p>Aquí puedes añadir el formulario específico para subir recursos educativos, distinto del formulario general.</p>';

echo $OUTPUT->footer();
?>
