<?php
require_once("../../config.php");
require_once("$CFG->libdir/formslib.php");
global $DB, $USER, $PAGE, $OUTPUT;

$courseid = required_param('courseid', PARAM_INT);
$pathid   = optional_param('pathid', 0, PARAM_INT);
$stepid   = optional_param('stepid', 0, PARAM_INT);

require_login($courseid);
$context = context_course::instance($courseid);
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/mod/learningstylesurvey/vista_estudiante.php', ['courseid'=>$courseid,'pathid'=>$pathid]));
$PAGE->set_title("Ruta de Aprendizaje");
$PAGE->set_heading("Ruta de Aprendizaje");

// Obtener estilo más reciente del usuario
$userstyle = $DB->get_record_sql("
    SELECT style
    FROM {learningstylesurvey_userstyles}
    WHERE userid = ?
    ORDER BY timecreated DESC
    LIMIT 1
", [$USER->id]);
$show_warning = false;
if (!$userstyle) {
    $show_warning = true;
    echo $OUTPUT->header();
    echo "<div class='container' style='max-width:600px; margin:40px auto;'>";
    echo "<div class='alert alert-warning' style='font-size:18px; background:#fff3cd; color:#856404; border:1px solid #ffeeba; padding:20px; border-radius:8px;'>";
    echo "<strong>¡Atención!</strong> Para acceder a la ruta de aprendizaje primero debes contestar la <b>encuesta de estilos de aprendizaje</b>.";
    echo "</div>";
    echo html_writer::link(new moodle_url('/mod/learningstylesurvey/surveyform.php', ['courseid' => $courseid]), 'Ir a la encuesta', ['class' => 'btn btn-primary', 'style' => 'font-size:18px; margin-top:20px;']);
    echo "</div>";
    echo $OUTPUT->footer();
    exit;
}
$style = $userstyle->style;

// Obtener ruta más reciente si no se pasa pathid
if (!$pathid) {
    $lastroute = $DB->get_record_sql("
        SELECT id 
        FROM {learningstylesurvey_paths} 
        WHERE courseid = ? 
        ORDER BY timecreated DESC LIMIT 1
    ", [$courseid]);
    if (!$lastroute) {
        throw new moodle_exception('No se encontró ninguna ruta para este curso.');
    }
    $pathid = $lastroute->id;
}

// Buscar el primer paso de la ruta que coincida con el estilo del usuario
$step = $DB->get_record_sql("
    SELECT s.*
    FROM {learningpath_steps} s
    JOIN {learningstylesurvey_resources} r ON s.resourceid = r.id
    WHERE s.pathid = ? AND r.style = ? AND s.istest = 0
    ORDER BY s.stepnumber ASC
    LIMIT 1
", [$pathid, $style]);

// Mostrar recurso relacionado con el estilo
echo $OUTPUT->header();
echo "<div class='container' style='max-width:900px; margin:20px auto;'>";
echo "<h2>Ruta de Aprendizaje ({$style})</h2>";
if ($step) {
    $resource = $DB->get_record('learningstylesurvey_resources', ['id'=>$step->resourceid]);
    $fileurl = "{$CFG->wwwroot}/mod/learningstylesurvey/uploads/{$resource->filename}";
    $ext = pathinfo($resource->filename, PATHINFO_EXTENSION);
    if (in_array(strtolower($ext), ['jpg','jpeg','png','gif'])) {
        echo "<img src='$fileurl' style='max-width:100%; height:auto; margin-bottom:20px;'>";
    } elseif (strtolower($ext) === 'pdf') {
        echo "<iframe src='$fileurl' style='width:100%; height:600px; border:none;'></iframe>";
    } elseif (in_array(strtolower($ext), ['mp4','webm'])) {
        echo "<video controls style='width:100%; max-height:500px;'><source src='$fileurl' type='video/$ext'>Tu navegador no soporta video HTML5.</video>";
    } else {
        echo "<a href='$fileurl' target='_blank'>Descargar recurso</a>";
    }
    // Botón de avance manual
    echo "<form method='POST' action='siguiente.php'>
            <input type='hidden' name='courseid' value='{$courseid}'>
            <input type='hidden' name='pathid' value='{$pathid}'>
            <input type='hidden' name='stepid' value='{$step->id}'>
            <button type='submit' class='btn btn-success' style='margin-top:15px;'>Continuar</button>
          </form>";
} else {
    echo "<div class='alert alert-info'>No hay recursos para tu estilo en esta ruta.</div>";
}

// Buscar el primer paso de examen programado en la ruta
$quizstep = $DB->get_record_sql("
    SELECT s.*
    FROM {learningpath_steps} s
    WHERE s.pathid = ? AND s.istest = 1
    ORDER BY s.stepnumber ASC
    LIMIT 1
", [$pathid]);
$cm = $DB->get_record_sql("
    SELECT cm.id 
    FROM {course_modules} cm
    JOIN {modules} m ON m.id = cm.module
    WHERE cm.course = ? AND m.name = 'learningstylesurvey'
    ORDER BY cm.id ASC LIMIT 1", [$courseid]);
if ($quizstep && $cm) {
    echo "<div style='margin-top:30px;'>";
    echo "<h3>Examen programado</h3>";
    echo html_writer::link(
        new moodle_url('/mod/learningstylesurvey/responder_quiz.php', [
            'id' => $quizstep->resourceid,
            'courseid' => $courseid,
            'embedded' => 0
        ]),
        'Ir al examen',
        ['class'=>'btn btn-primary']
    );
    echo "</div>";
}

// Botón regresar al menú
if ($cm) {
    $menuurl = new moodle_url('/mod/learningstylesurvey/view.php', ['id'=>$cm->id]);
    echo "<div style='margin-top:30px;'><a href='{$menuurl}' class='btn btn-secondary'>Regresar al menú</a></div>";
}

echo "</div>";
echo $OUTPUT->footer();
?>
