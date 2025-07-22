<?php
require_once('../../config.php');

$id = required_param('id', PARAM_INT); // id del módulo
$cm = get_coursemodule_from_id('learningstylesurvey', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_once('../../config.php');
require_login();
$coursecontext = context_course::instance($course->id);




if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submitbutton'])) {
    global $DB, $USER;

    $surveyid = $cm->instance;

    foreach ($_POST as $key => $value) {
        if (strpos($key, 'q') === 0) {
            $questionid = intval(substr($key, 1));
            $response = clean_param($value, PARAM_TEXT);

            // Verificar si ya existe la respuesta
            $existing = $DB->get_record('learningstylesurvey_responses', [
                'userid' => $USER->id,
                'surveyid' => $surveyid,
                'questionid' => $questionid
            ]);

            if (!$existing) {
                $record = new stdClass();
                $record->userid = $USER->id;
                $record->surveyid = $surveyid;
                $record->questionid = $questionid;
                $record->response = $response;
                $DB->insert_record('learningstylesurvey_responses', $record);
            }
        }
    }

    // Guardar estilo más fuerte simulado
    $style = 'Visual';
    $existing = $DB->get_record('learningstylesurvey_results', ['userid' => $USER->id]);
    if (!$existing) {
        $result = new stdClass();
        $result->userid = $USER->id;
        $result->strongeststyle = $style;
        $DB->insert_record('learningstylesurvey_results', $result);
    }

    redirect(new moodle_url("/mod/learningstylesurvey/results.php", ['id' => $id]));
}
$id = required_param('id', PARAM_INT); // ID de la instancia del módulo
$cm = get_coursemodule_from_id('learningstylesurvey', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$context = context_module::instance($cm->id);

$PAGE->set_cm($cm, $course);

require_capability('mod/learningstylesurvey:view', $context);

$PAGE->set_url('/mod/learningstylesurvey/view.php', array('id' => $id));
$PAGE->set_context($context);
$PAGE->set_title(format_string("Encuesta ILS"));
$PAGE->set_heading(format_string($course->fullname));


echo $OUTPUT->header();

if (!has_capability('moodle/course:update', $context)) {
    echo "<div style='margin: 20px 0; text-align: center;'>";
    echo "<a href='vista_estudiante.php?courseid=$course->id'>";
    echo "<button style='background:#0073e6; color:white; font-size:18px; padding:15px 25px; border:none; border-radius:8px; cursor:pointer;'>🧭 Comenzar Ruta Informativa</button>";
    echo "</a>";
    echo "</div>";
}

echo $OUTPUT->heading("Menú principal");

echo html_writer::start_tag('ul');
echo html_writer::tag('li', html_writer::link(new moodle_url('/mod/learningstylesurvey/surveyform.php', ['id' => $id]), 'Responder encuesta'));
echo html_writer::tag('li', html_writer::link(new moodle_url('/mod/learningstylesurvey/results.php', ['id' => $id]), 'Ver resultados'));
echo html_writer::tag('li', html_writer::link(new moodle_url('/mod/learningstylesurvey/viewresources.php', ['courseid' => $course->id]), 'Ver archivos'));
echo html_writer::tag('li', html_writer::link(new moodle_url('/mod/learningstylesurvey/uploadresource.php', ['courseid' => $course->id]), 'Subir archivos'));
echo html_writer::tag('li', html_writer::link(new moodle_url('/mod/learningstylesurvey/crear_examen.php', ['courseid' => $course->id]), 'Crear Evaluacion'));
echo html_writer::tag('li', html_writer::link(new moodle_url('/mod/learningstylesurvey/learningpath.php', ['courseid' => $course->id]), 'Ruta de Aprendizaje'));
echo html_writer::end_tag('ul');

echo $OUTPUT->footer();
