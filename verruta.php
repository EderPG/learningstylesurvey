<?php
require_once('../../config.php');
require_login();

global $DB, $USER;

$courseid = required_param('courseid', PARAM_INT);
$context = context_course::instance($courseid);
require_capability('mod/learningstylesurvey:view', $context);

$PAGE->set_url('/mod/learningstylesurvey/verruta.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_title('Mi Ruta de Aprendizaje');
$PAGE->set_heading('Mi Ruta de Aprendizaje');

echo $OUTPUT->header();
echo $OUTPUT->heading('Pasos asignados a tu estilo de aprendizaje');

// Obtener estilo más fuerte del usuario
$style = $DB->get_field('learningstylesurvey_userstyle', 'strongeststyle', ['userid' => $USER->id]);
if (!$style) {
    echo $OUTPUT->notification('Aún no has completado la encuesta de estilos de aprendizaje.');
    echo $OUTPUT->footer();
    exit;
}

// Obtener recursos compatibles con su estilo y mostrar pasos
$sql = "SELECT s.* FROM {learningpath_steps} s
         JOIN {learningpath} p ON s.pathid = p.id
         JOIN {learningstylesurvey_files} f ON s.resourceid = f.id
         WHERE f.style = :style AND p.courseid = :courseid
         ORDER BY s.stepnumber ASC";

$steps = $DB->get_records_sql($sql, ['style' => $style, 'courseid' => $courseid]);

if (!$steps) {
    echo $OUTPUT->notification('No hay pasos asignados para tu estilo de aprendizaje aún.');
} else {
    echo html_writer::start_tag('ol');
    foreach ($steps as $step) {
        echo html_writer::tag('li', 'Paso ' . $step->stepnumber . ' - Archivo ID: ' . $step->resourceid . ($step->istest ? ' [Examen]' : ''));
    }
    echo html_writer::end_tag('ol');
}

echo $OUTPUT->footer();
