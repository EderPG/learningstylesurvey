<?php
require_once('../../config.php');
require_login();

global $DB, $USER;

$quizid = required_param('id', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$userid = $USER->id;

// Cargar información del cuestionario
$quiz = $DB->get_record('learningstylesurvey_quizzes', ['id' => $quizid], '*', MUST_EXIST);
$questions = $DB->get_records('learningstylesurvey_questions', ['quizid' => $quizid]);

$PAGE->set_url(new moodle_url('/mod/learningstylesurvey/responder_quiz.php', ['id' => $quizid, 'courseid' => $courseid]));
$PAGE->set_context(context_course::instance($courseid));
$PAGE->set_title('Responder Cuestionario');
$PAGE->set_heading('Responder Cuestionario');

echo $OUTPUT->header();
echo "<div class='box generalbox' style='padding: 20px; max-width: 800px; margin: 0 auto;'>";
echo $OUTPUT->heading('Cuestionario: ' . format_string($quiz->name), 3);

// Verificar si ya respondió
$result = $DB->get_record('learningstylesurvey_quiz_results', [
    'userid' => $userid,
    'quizid' => $quizid,
    'courseid' => $courseid
]);

if ($result) {
    echo "<div style='text-align:center; margin-top:20px;'>";
    if ($result->score < 70 && $quiz->recoveryquizid) {
        $url = new moodle_url('/mod/learningstylesurvey/responder_quiz.php', ['id' => $quiz->recoveryquizid, 'courseid' => $courseid]);
        echo "<div style='margin-top:20px;'>
                <a class='btn btn-primary' href='{$url}'>Realizar Examen de Recuperación</a>
              </div>";
    }
    echo "<h3>Resultado previo: {$result->score}%</h3>";
    echo $result->score >= 70
        ? "<p style='color:green; font-weight:bold;'>¡Aprobado!</p>"
        : "<p style='color:red; font-weight:bold;'>Reprobado</p>";
    echo "<a href='vista_estudiante.php?courseid={$courseid}'>Volver</a>";
    echo "</div>";
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $total = count($questions);
    $correct = 0;

    foreach ($questions as $q) {
        $userAnswer = optional_param("question{$q->id}", null, PARAM_INT);
        if ($userAnswer !== null && $userAnswer == $q->correctanswer) {
            $correct++;
        }
    }

    $score = round(($correct / $total) * 100);

    $record = new stdClass();
    $record->userid = $userid;
    $record->quizid = $quizid;
    $record->courseid = $courseid;
    $record->score = $score;
    $record->timemodified = time();
    $record->timecompleted = time();
    $DB->insert_record('learningstylesurvey_quiz_results', $record);

    redirect(new moodle_url('/mod/learningstylesurvey/responder_quiz.php', ['id' => $quizid, 'courseid' => $courseid]));
} else {
    echo '<form method="post">';

    foreach ($questions as $index => $q) {
        // Obtener las opciones desde tabla learningstylesurvey_options
        $options = $DB->get_records('learningstylesurvey_options', ['questionid' => $q->id]);

        echo "<fieldset style='margin-bottom:20px;'><legend><b>" . ($index + 1) . ". {$q->questiontext}</b></legend>";
        $i = 0;
        foreach ($options as $opt) {
            echo "<label style='display:block; margin-bottom:6px;'>
                    <input type='radio' name='question{$q->id}' value='{$i}'> {$opt->optiontext}
                  </label>";
            $i++;
        }
        echo "</fieldset>";
    }

    echo '<div style="text-align:center;"><button type="submit" style="padding:10px 20px; font-size:16px;">Enviar respuestas</button></div>';
    echo '</form>';
}

echo "</div>";
echo $OUTPUT->footer();
?>
