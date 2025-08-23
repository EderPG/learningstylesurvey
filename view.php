<?php
require_once('../../config.php');

$id = required_param('id', PARAM_INT); // ID del módulo
$cm = get_coursemodule_from_id('learningstylesurvey', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
$PAGE->set_cm($cm, $course);
$PAGE->set_url('/mod/learningstylesurvey/view.php', ['id' => $id]);
$PAGE->set_context($context);
$PAGE->set_title(format_string("Encuesta ILS"));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();

// ✅ Mostrar encabezado
echo $OUTPUT->heading("Menú principal");

// ✅ Si es ESTUDIANTE (no tiene permiso para editar el curso)
if (!has_capability('moodle/course:update', $context)) {
    echo "<div style='margin: 20px 0; text-align: center;'>";
    echo "<a href='vista_estudiante.php?courseid={$course->id}' style='text-decoration:none;'>";
    echo "<button style='background:#0073e6; color:white; font-size:18px; padding:15px 25px; border:none; border-radius:8px; cursor:pointer;'>🧭 Comenzar Ruta Aprendizaje Adaptativa</button>";
    echo "</a>";
    echo "</div>";

    // ✅ Opciones disponibles para estudiantes
    echo html_writer::start_tag('ul', ['style' => 'list-style:none; padding:0; text-align:center; font-size:18px;']);
    echo html_writer::tag('li', html_writer::link(new moodle_url('/mod/learningstylesurvey/surveyform.php', ['id' => $id]), '📋 Responder encuesta de estilos de aprendizaje', ['style' => 'display:block; margin:10px 0;']));
    echo html_writer::tag('li', html_writer::link(new moodle_url('/mod/learningstylesurvey/results.php', ['id' => $id]), '📊 Ver resultados', ['style' => 'display:block; margin:10px 0;']));
    echo html_writer::end_tag('ul');

} else {
    // ✅ Opciones completas para profesores/admins
    echo html_writer::start_tag('ul', ['style' => 'list-style:none; padding:0; font-size:18px;']);
    echo html_writer::tag('li', html_writer::link(new moodle_url('/mod/learningstylesurvey/surveyform.php', ['id' => $id]), '📋 Responder encuesta', ['style' => 'display:block; margin:10px 0;']));
    echo html_writer::tag('li', html_writer::link(new moodle_url('/mod/learningstylesurvey/results.php', ['id' => $id]), '📊 Ver resultados', ['style' => 'display:block; margin:10px 0;']));
    echo html_writer::tag('li', html_writer::link(new moodle_url('/mod/learningstylesurvey/viewresources.php', ['courseid' => $course->id]), '📂 Ver archivos', ['style' => 'display:block; margin:10px 0;']));
    echo html_writer::tag('li', html_writer::link(new moodle_url('/mod/learningstylesurvey/uploadresource.php', ['courseid' => $course->id]), '⬆️ Subir archivos', ['style' => 'display:block; margin:10px 0;']));
    echo html_writer::tag('li', html_writer::link(new moodle_url('/mod/learningstylesurvey/temas.php', ['courseid' => $course->id]), '📊 Temas a Revisar', ['style' => 'display:block; margin:10px 0;']));
    echo html_writer::tag('li', html_writer::link(new moodle_url('/mod/learningstylesurvey/crear_examen.php', ['courseid' => $course->id]), '📝 Crear Evaluación', ['style' => 'display:block; margin:10px 0;']));
    echo html_writer::tag('li', html_writer::link(new moodle_url('/mod/learningstylesurvey/manage_quiz.php', ['courseid' => $course->id]), '🛠 Gestionar exámenes', ['style' => 'display:block; margin:10px 0;']));
    echo html_writer::tag('li', html_writer::link(new moodle_url('/mod/learningstylesurvey/learningpath.php', ['courseid' => $course->id]), '🛤 Ruta de Aprendizaje', ['style' => 'display:block; margin:10px 0;']));
    echo html_writer::end_tag('ul');
}

echo $OUTPUT->footer();
