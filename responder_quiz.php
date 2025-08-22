<?php
require_once('../../config.php');
global $DB, $USER, $OUTPUT;

// Detectar si se carga embebido
$embedded = optional_param('embedded', 0, PARAM_INT) == 1;

$quizid   = required_param('id', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$userid   = $USER->id;

if (!$embedded) {
    require_login($courseid);
    $PAGE->set_url(new moodle_url('/mod/learningstylesurvey/responder_quiz.php', ['id' => $quizid, 'courseid' => $courseid]));
    $PAGE->set_context(context_course::instance($courseid));
    $PAGE->set_title('Responder Cuestionario');
    $PAGE->set_heading('Responder Cuestionario');
    echo $OUTPUT->header();
    echo "<div class='box generalbox' style='padding: 20px; max-width: 800px; margin: 0 auto;'>";
    echo $OUTPUT->heading('Cuestionario: ' . format_string($DB->get_field('learningstylesurvey_quizzes','name',['id'=>$quizid])), 3);
} else {
    echo "<div class='embedded-quiz' style='padding:15px; border:1px solid #ccc; margin-bottom:20px;'>";
    echo "<h4>Cuestionario: " . format_string($DB->get_field('learningstylesurvey_quizzes','name',['id'=>$quizid])) . "</h4>";
}

// Función para procesar envío
function process_quiz_submission($quizid, $courseid, $userid, $embedded = false) {
    global $DB;

    $questions = $DB->get_records('learningstylesurvey_questions', ['quizid' => $quizid]);
    $total = count($questions);
    $correct = 0;

    foreach ($questions as $q) {
        $userAnswer = optional_param("question{$q->id}", null, PARAM_INT);

        // Obtener opciones ordenadas para comparar el índice
        $options = $DB->get_records('learningstylesurvey_options', ['questionid' => $q->id]);
        $options = array_values($options); // asegurar orden

        // Buscar el índice de la opción correcta
        $correctIndex = 0;
        foreach ($options as $idx => $opt) {
            if ($opt->is_correct) { // asumiendo que tienes un campo is_correct
                $correctIndex = $idx;
                break;
            }
        }

        if ($userAnswer !== null && $userAnswer == $correctIndex) {
            $correct++;
        }
    }

    $score = ($total > 0) ? round(($correct / $total) * 100) : 0;

    // Buscar si ya existe resultado
    $existing = $DB->get_record('learningstylesurvey_quiz_results', [
        'userid' => $userid,
        'quizid' => $quizid,
        'courseid' => $courseid
    ]);

    $record = new stdClass();
    $record->userid = $userid;
    $record->quizid = $quizid;
    $record->courseid = $courseid;
    $record->score = $score;
    $record->timemodified = time();
    $record->timecompleted = time();

    if ($existing) {
        $record->id = $existing->id;
        $DB->update_record('learningstylesurvey_quiz_results', $record);
    } else {
        $DB->insert_record('learningstylesurvey_quiz_results', $record);
    }

    return $score;
}

// Verificar si ya respondió
$result = $DB->get_record('learningstylesurvey_quiz_results', [
    'userid' => $userid,
    'quizid' => $quizid,
    'courseid' => $courseid
]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $score = process_quiz_submission($quizid, $courseid, $userid, $embedded);
    echo "<div style='text-align:center; margin-top:20px;'>";
    echo "<h3>Resultado: {$score}%</h3>";
    echo $score >= 70
        ? "<p style='color:green; font-weight:bold;'>¡Aprobado!</p>"
        : "<p style='color:red; font-weight:bold;'>Reprobado</p>";
    echo "</div>";
} else if ($result) {
    echo "<div style='text-align:center; margin-top:20px;'>";
    echo "<h3>Resultado previo: {$result->score}%</h3>";
    echo $result->score >= 70
        ? "<p style='color:green; font-weight:bold;'>¡Aprobado!</p>"
        : "<p style='color:red; font-weight:bold;'>Reprobado</p>";
    if ($result->score < 70 && $quiz = $DB->get_record('learningstylesurvey_quizzes',['id'=>$quizid]) && $quiz->recoveryquizid) {
        $url = new moodle_url('/mod/learningstylesurvey/responder_quiz.php', [
            'id' => $quiz->recoveryquizid,
            'courseid' => $courseid,
            'embedded' => $embedded ? 1 : 0
        ]);
        echo "<div style='margin-top:20px;'>
                <a class='btn btn-primary' href='{$url}'>Realizar Examen de Recuperación</a>
              </div>";
    }
    if (!$embedded) {
        echo "<a href='vista_estudiante.php?courseid={$courseid}'>Volver</a>";
    }
    echo "</div>";
} else {
    // Mostrar formulario
    $questions = $DB->get_records('learningstylesurvey_questions', ['quizid' => $quizid]);
    echo '<form method="post">';
    foreach ($questions as $index => $q) {
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
?>
