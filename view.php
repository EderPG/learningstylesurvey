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
    $vista_url = new moodle_url('/mod/learningstylesurvey/path/vista_estudiante.php', ['courseid' => $course->id, 'cmid' => $id]);
    echo "<a href='" . $vista_url->out() . "' style='text-decoration:none;'>";
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
    echo html_writer::tag('li', html_writer::link(new moodle_url('/mod/learningstylesurvey/resource/viewresources.php', ['courseid' => $course->id, 'cmid' => $id]), '📂 Ver archivos', ['style' => 'display:block; margin:10px 0;']));
    echo html_writer::tag('li', html_writer::link(new moodle_url('/mod/learningstylesurvey/resource/uploadresource.php', ['courseid' => $course->id, 'cmid' => $id]), '⬆️ Subir archivos', ['style' => 'display:block; margin:10px 0;']));
    echo html_writer::tag('li', html_writer::link(new moodle_url('/mod/learningstylesurvey/resource/temas.php', ['courseid' => $course->id, 'cmid' => $id]), '📊 Temas a Revisar', ['style' => 'display:block; margin:10px 0;']));
    echo html_writer::tag('li', html_writer::link(new moodle_url('/mod/learningstylesurvey/quiz/crear_examen.php', ['courseid' => $course->id, 'cmid' => $id]), '📝 Crear Evaluación', ['style' => 'display:block; margin:10px 0;']));
    echo html_writer::tag('li', html_writer::link(new moodle_url('/mod/learningstylesurvey/quiz/manage_quiz.php', ['courseid' => $course->id, 'cmid' => $id]), '🛠 Gestionar exámenes', ['style' => 'display:block; margin:10px 0;']));
    echo html_writer::tag('li', html_writer::link(new moodle_url('/mod/learningstylesurvey/path/learningpath.php', ['courseid' => $course->id, 'cmid' => $id]), '🛤 Ruta de Aprendizaje', ['style' => 'display:block; margin:10px 0;']));
    
    // Solo mostrar "Verificar Funcionalidades" a administradores (NO a profesores)
    if (is_siteadmin($USER)) {
        echo html_writer::tag('li', html_writer::link(new moodle_url('/mod/learningstylesurvey/utils/verificar_funcionalidades.php', ['courseid' => $course->id, 'id' => $id]), '🔧 Verificar Funcionalidades', ['style' => 'display:block; margin:10px 0;']));
    }
    
    echo html_writer::end_tag('ul');
}

echo $OUTPUT->footer();
