<?php
require_once('../../config.php');
global $DB, $USER, $OUTPUT;

// Detectar si se carga embebido
$embedded = optional_param('embedded', 0, PARAM_INT) == 1;
$retry = optional_param('retry', 0, PARAM_INT) == 1;

$quizid   = required_param('id', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$userid   = $USER->id;

require_login($courseid);
$PAGE->set_url(new moodle_url('/mod/learningstylesurvey/responder_quiz.php', ['id' => $quizid, 'courseid' => $courseid, 'embedded' => $embedded ? 1 : 0]));
$PAGE->set_context(context_course::instance($courseid));
$PAGE->set_title('Responder Cuestionario');
$PAGE->set_heading('Responder Cuestionario');
$PAGE->set_pagelayout('incourse'); // Esto hace que se vea dentro del estilo Moodle
echo $OUTPUT->header();
echo "<div class='box generalbox' style='padding: 20px; max-width: 800px; margin: 0 auto;'>";
echo $OUTPUT->heading('Cuestionario: ' . format_string($DB->get_field('learningstylesurvey_quizzes','name',['id'=>$quizid])), 3);
if ($embedded) {
    $returnurl = new moodle_url('/mod/learningstylesurvey/vista_estudiante.php', ['courseid' => $courseid]);
    echo "<div style='margin-bottom:15px;'><a href='" . $returnurl->out() . "' class='btn btn-secondary'>Regresar a la ruta</a></div>";
}

// Si es un reintento, limpiar el resultado anterior
if ($retry) {
    $DB->delete_records('learningstylesurvey_quiz_results', [
        'userid' => $userid,
        'quizid' => $quizid,
        'courseid' => $courseid
    ]);
    echo "<div class='alert alert-info'>Resultado anterior eliminado. Puedes volver a realizar el examen.</div>";
}

// Función para procesar envío
function process_quiz_submission($quizid, $courseid, $userid, $embedded = false) {
    global $DB;

    $questions = $DB->get_records('learningstylesurvey_questions', ['quizid' => $quizid]);
    $total = count($questions);
    $correct = 0;

    foreach ($questions as $q) {
        $userOptionId = optional_param("question{$q->id}", null, PARAM_INT);
        $options = $DB->get_records('learningstylesurvey_options', ['questionid' => $q->id]);
        $selectedText = null;
        if ($userOptionId !== null && isset($options[$userOptionId])) {
            $selectedText = $options[$userOptionId]->optiontext;
        } else {
            // Buscar por id
            foreach ($options as $opt) {
                if ($opt->id == $userOptionId) {
                    $selectedText = $opt->optiontext;
                    break;
                }
            }
        }
        if ($selectedText !== null && trim($selectedText) === trim($q->correctanswer)) {
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

// Verificar si ya respondió (solo si no es un reintento)
$result = null;
if (!$retry) {
    $result = $DB->get_record('learningstylesurvey_quiz_results', [
        'userid' => $userid,
        'quizid' => $quizid,
        'courseid' => $courseid
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $score = process_quiz_submission($quizid, $courseid, $userid, $embedded);
    echo "<div style='text-align:center; margin-top:20px;'>";
    echo "<h3>Resultado: {$score}%</h3>";
    if ($score >= 70) {
        echo "<p style='color:green; font-weight:bold;'>¡Aprobado!</p>";
        // Buscar el paso de examen correcto para obtener saltos programados
        $step = $DB->get_record_sql("
            SELECT s.* FROM {learningpath_steps} s 
            WHERE s.resourceid = ? AND s.istest = 1
            ORDER BY s.id DESC LIMIT 1
        ", [$quizid]);
        
        if ($step && $step->passredirect) {
            // El salto apunta a un tema ID, buscar el primer recurso de ese tema
            $target_resource = $DB->get_record_sql("
                SELECT r.* FROM {learningstylesurvey_resources} r 
                WHERE r.tema = ? 
                ORDER BY r.id ASC LIMIT 1
            ", [$step->passredirect]);
            
            if ($target_resource) {
                $nexturl = new moodle_url('/mod/learningstylesurvey/vista_estudiante.php', [
                    'courseid' => $courseid,
                    'pathid' => $step->pathid,
                    'tema_salto' => $step->passredirect
                ]);
                echo "<div style='margin-top:20px;'><a class='btn btn-success' href='" . $nexturl->out() . "'>Continuar al tema asignado</a></div>";
            }
        } else {
            // Si no hay salto configurado, buscar el siguiente paso en orden normal
            if ($step) {
                $nextstep = $DB->get_record_sql("
                    SELECT * FROM {learningpath_steps}
                    WHERE pathid = ? AND stepnumber > ?
                    ORDER BY stepnumber ASC LIMIT 1",
                    [$step->pathid, $step->stepnumber]
                );
                if ($nextstep) {
                    $nexturl = new moodle_url('/mod/learningstylesurvey/vista_estudiante.php', [
                        'courseid' => $courseid,
                        'pathid' => $step->pathid,
                        'stepid' => $nextstep->id
                    ]);
                    echo "<div style='margin-top:20px;'><a class='btn btn-success' href='" . $nexturl->out() . "'>Continuar al siguiente paso</a></div>";
                }
            }
        }
    } else {
        echo "<p style='color:red; font-weight:bold;'>Reprobado</p>";
        // Buscar salto a refuerzo por tema
        $step = $DB->get_record_sql("
            SELECT s.* FROM {learningpath_steps} s 
            WHERE s.resourceid = ? AND s.istest = 1
            ORDER BY s.id DESC LIMIT 1
        ", [$quizid]);
        
        if ($step && $step->failredirect) {
            // El salto apunta a un tema ID de refuerzo
            $refuerzourl = new moodle_url('/mod/learningstylesurvey/vista_estudiante.php', [
                'courseid' => $courseid,
                'pathid' => $step->pathid,
                'tema_refuerzo' => $step->failredirect
            ]);
            echo "<div style='margin-top:20px;'>";
            echo "<a class='btn btn-warning' href='" . $refuerzourl->out() . "'>Ir al tema de refuerzo</a>";
            echo "</div>";
        } else {
            // Si no hay salto de refuerzo configurado, mostrar mensaje
            echo "<div style='margin-top:20px;'>";
            echo "<p>No hay tema de refuerzo configurado. Debes estudiar más antes de volver a intentar.</p>";
            echo "</div>";
        }
    }
    
    // Agregar botón volver en todos los casos
    $returnurl = new moodle_url('/mod/learningstylesurvey/vista_estudiante.php', ['courseid' => $courseid]);
    echo "<div style='margin-top:15px;'>";
    echo "<a href='" . $returnurl->out() . "' class='btn btn-secondary'>Volver</a>";
    echo "</div>";
    
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
        $volver_url = new moodle_url('/mod/learningstylesurvey/vista_estudiante.php', ['courseid' => $courseid]);
        echo "<a href='" . $volver_url->out() . "'>Volver</a>";
    }
    echo "</div>";
} else {
    // Mostrar formulario
    $questions = $DB->get_records('learningstylesurvey_questions', ['quizid' => $quizid]);
    echo '<form method="post">';
    foreach ($questions as $index => $q) {
        $options = $DB->get_records('learningstylesurvey_options', ['questionid' => $q->id]);
        echo "<fieldset style='margin-bottom:20px;'><legend><b>" . ($index + 1) . ". {$q->questiontext}</b></legend>";
        foreach ($options as $opt) {
            echo "<label style='display:block; margin-bottom:6px;'>
                    <input type='radio' name='question{$q->id}' value='{$opt->id}'> {$opt->optiontext}
                  </label>";
        }
        echo "</fieldset>";
    }
    echo '<div style="text-align:center;"><button type="submit" style="padding:10px 20px; font-size:16px;">Enviar respuestas</button></div>';
    echo '</form>';
}

echo "</div>";
?>
