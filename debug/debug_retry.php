<?php
require_once('../../../config.php');
global $DB, $USER, $OUTPUT;

$courseid = required_param('courseid', PARAM_INT);
$quizid = optional_param('quizid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_TEXT);

require_login($courseid);
$PAGE->set_url(new moodle_url('/mod/learningstylesurvey/debug/debug_retry.php', ['courseid' => $courseid]));
$PAGE->set_context(context_course::instance($courseid));
$PAGE->set_title('Debug Reintentos');
$PAGE->set_heading('Debug Reintentos');

echo $OUTPUT->header();
echo "<div class='container-fluid'>";
echo $OUTPUT->heading('Debug del Sistema de Reintentos');

if ($action === 'clear' && $quizid) {
    $deleted = $DB->delete_records('learningstylesurvey_quiz_results', [
        'userid' => $USER->id,
        'quizid' => $quizid,
        'courseid' => $courseid
    ]);
    
    echo "<div class='alert alert-success'>Eliminados $deleted registros de resultados para el quiz ID $quizid</div>";
}

// Mostrar todos los resultados del usuario
$results = $DB->get_records_sql("
    SELECT qr.*, q.name as quiz_name 
    FROM {learningstylesurvey_quiz_results} qr
    JOIN {learningstylesurvey_quizzes} q ON q.id = qr.quizid
    WHERE qr.userid = ? AND qr.courseid = ?
    ORDER BY qr.timecompleted DESC
", [$USER->id, $courseid]);

echo "<h3>Resultados actuales del usuario:</h3>";
if ($results) {
    echo "<table class='table'>";
    echo "<tr><th>Quiz</th><th>Score</th><th>Fecha</th><th>Acciones</th></tr>";
    foreach ($results as $result) {
        $date = date('Y-m-d H:i:s', $result->timecompleted);
        $status = $result->score >= 70 ? '<span style="color:green">Aprobado</span>' : '<span style="color:red">Reprobado</span>';
        
        echo "<tr>";
        echo "<td>" . format_string($result->quiz_name) . "</td>";
        echo "<td>{$result->score}% ($status)</td>";
        echo "<td>$date</td>";
        echo "<td>";
        echo "<a href='?courseid=$courseid&quizid={$result->quizid}&action=clear' class='btn btn-danger btn-sm'>Limpiar resultado</a> ";
        echo "<a href='../quiz/responder_quiz.php?id={$result->quizid}&courseid=$courseid&embedded=1&retry=1' class='btn btn-primary btn-sm'>Reintentar</a>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No hay resultados registrados.</p>";
}

// Mostrar información de pasos y saltos
echo "<h3>Información de pasos y saltos:</h3>";
$steps = $DB->get_records_sql("
    SELECT s.*, r.filename, q.name as quiz_name
    FROM {learningpath_steps} s
    LEFT JOIN {learningstylesurvey_resources} r ON r.id = s.resourceid AND s.istest = 0
    LEFT JOIN {learningstylesurvey_quizzes} q ON q.id = s.resourceid AND s.istest = 1
    WHERE s.pathid IN (SELECT id FROM {learningstylesurvey_paths} WHERE courseid = ?)
    ORDER BY s.pathid, s.stepnumber
", [$courseid]);

if ($steps) {
    echo "<table class='table'>";
    echo "<tr><th>Paso</th><th>Tipo</th><th>Recurso</th><th>Salto Aprueba</th><th>Salto Reprueba</th></tr>";
    foreach ($steps as $step) {
        $type = $step->istest ? 'Evaluación' : 'Recurso';
        $resource = $step->istest ? $step->quiz_name : $step->filename;
        
        echo "<tr>";
        echo "<td>{$step->stepnumber}</td>";
        echo "<td>$type</td>";
        echo "<td>" . format_string($resource) . "</td>";
        echo "<td>" . ($step->passredirect ? "Paso {$step->passredirect}" : 'Secuencial') . "</td>";
        echo "<td>" . ($step->failredirect ? "Paso {$step->failredirect}" : 'Secuencial') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<div style='margin-top: 30px;'>";
echo "<a href='../path/vista_estudiante.php?courseid=$courseid' class='btn btn-secondary'>Volver a la ruta</a>";
echo "</div>";

echo "</div>";
echo $OUTPUT->footer();
?>
