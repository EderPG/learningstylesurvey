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

// Función para procesar envío
function process_quiz_submission($quizid, $courseid, $userid, $embedded = false) {
    global $DB;

    $questions = $DB->get_records('learningstylesurvey_questions', ['quizid' => $quizid]);
    $total = count($questions);
    $correct = 0;

    foreach ($questions as $q) {
        $userOptionId = optional_param("question{$q->id}", null, PARAM_INT);
        // ✅ Ordenar opciones por ID para mantener consistencia con el índice guardado
        $options = $DB->get_records('learningstylesurvey_options', ['questionid' => $q->id], 'id ASC');
        $selectedText = null;
        $selectedIndex = null;
        
        // Buscar la opción seleccionada por ID
        if ($userOptionId !== null) {
            foreach ($options as $opt) {
                if ($opt->id == $userOptionId) {
                    $selectedText = $opt->optiontext;
                    break;
                }
            }
        }
        
        // ✅ Encontrar el índice correcto (0, 1, 2...) basado en el orden de las opciones
        if ($userOptionId !== null) {
            $optionIndex = 0; // ✅ Empezar desde 0, no desde 1
            foreach ($options as $opt) {
                if ($opt->id == $userOptionId) {
                    $selectedIndex = $optionIndex;
                    break;
                }
                $optionIndex++;
            }
        }
        
        // Debug temporal - registrar comparaciones
        error_log("Question {$q->id}: UserOptionId=$userOptionId, Selected='$selectedText' (index $selectedIndex), Correct='{$q->correctanswer}'");
        error_log("Question {$q->id}: Available options: " . json_encode(array_map(function($opt) { return ['id' => $opt->id, 'text' => $opt->optiontext]; }, $options)));
        
        // ✅ Verificación robusta para correctanswer (maneja tanto índice numérico como texto)
        $isCorrect = false;
        if (is_numeric($q->correctanswer)) {
            // Nuevo formato: índice numérico (0, 1, 2, 3...)
            $isCorrect = ($selectedIndex !== null && (int)$q->correctanswer == $selectedIndex);
            error_log("Question {$q->id}: Comparing index - Selected: $selectedIndex vs Correct: {$q->correctanswer} = " . ($isCorrect ? 'CORRECT' : 'INCORRECT'));
        } else {
            // Formato antiguo: texto de la opción
            $isCorrect = ($selectedText !== null && trim(strtolower($selectedText)) === trim(strtolower($q->correctanswer)));
            error_log("Question {$q->id}: Comparing text - Selected: '$selectedText' vs Correct: '{$q->correctanswer}' = " . ($isCorrect ? 'CORRECT' : 'INCORRECT'));
        }
        
        if ($isCorrect) {
            $correct++;
        }
    }

    $score = ($total > 0) ? round(($correct / $total) * 100) : 0;
    
    // Debug temporal - mostrar cálculos
    error_log("Quiz calculation: $correct correct out of $total questions = $score%");

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

    // CAMBIO IMPORTANTE: Siempre crear un nuevo registro para cada intento
    // Esto permite un seguimiento preciso del progreso del estudiante
    $DB->insert_record('learningstylesurvey_quiz_results', $record);
    error_log("Quiz attempt: Inserted new result for user $userid, quiz $quizid, score: $score, time: " . date('Y-m-d H:i:s'));

    return $score;
}

// Verificar si ya respondió
$result = $DB->get_record('learningstylesurvey_quiz_results', [
    'userid' => $userid,
    'quizid' => $quizid,
    'courseid' => $courseid
]);

// Si es un reintento y existe un resultado previo, eliminarlo
if ($retry && $result) {
    $deleted = $DB->delete_records('learningstylesurvey_quiz_results', [
        'userid' => $userid,
        'quizid' => $quizid,
        'courseid' => $courseid
    ]);
    echo "<div class='alert alert-success'>✅ Resultado anterior eliminado. Puedes realizar el examen nuevamente.</div>";
    $result = null; // Limpiar la variable para permitir mostrar el formulario
}

// Lógica mejorada: permitir reintentos automáticos si el resultado previo es reprobatorio
$can_retry = false;
$auto_retry = false;

if ($result && $result->score < 70) {
    // Resultado reprobatorio - permitir reintento automático
    echo "<div class='alert alert-warning'>⚠️ Resultado anterior reprobatorio ({$result->score}%). Puedes volver a intentarlo las veces que necesites.</div>";
    echo "<div class='alert alert-info'>💡 <strong>Tip:</strong> Si repruebas, podrás acceder a material de refuerzo y volver a intentarlo.</div>";
    $auto_retry = true;
    $can_retry = true;
}

// Debug: Mostrar información sobre el estado
if ($result && !$retry && !$auto_retry) {
    echo "<div class='alert alert-success' style='font-size: 14px;'>
        ✅ Examen ya completado - Score: {$result->score}% - " . date('Y-m-d H:i:s', $result->timecompleted) . "
    </div>";
} else if ($retry) {
    echo "<div class='alert alert-success'>🔄 Reintento solicitado</div>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $score = process_quiz_submission($quizid, $courseid, $userid, $embedded);
    
    // Obtener el mejor score guardado (puede ser diferente al actual si hubo intentos previos)
    $best_result = $DB->get_record('learningstylesurvey_quiz_results', [
        'userid' => $userid,
        'quizid' => $quizid,
        'courseid' => $courseid
    ]);
    $best_score = $best_result ? $best_result->score : $score;
    
    echo "<div style='text-align:center; margin-top:20px;'>";
    echo "<h3>Resultado actual: {$score}%</h3>";
    
    if ($best_score != $score) {
        echo "<p style='color:#007bff; font-weight:bold;'>Mejor resultado: {$best_score}%</p>";
    }
    
    if ($best_score >= 70) {
        echo "<p style='color:green; font-weight:bold;'>¡Aprobado!</p>";
        if ($score < 70) {
            echo "<div class='alert alert-success'>✅ Aunque este intento fue {$score}%, tu mejor resultado ({$best_score}%) ya aprueba el examen.</div>";
        }
    } else {
        echo "<p style='color:red; font-weight:bold;'>Reprobado</p>";
        echo "<div class='alert alert-info'>💡 Puedes volver a intentarlo las veces que necesites. Solo se guardará tu mejor resultado.</div>";
    }
    
    if ($best_score >= 70) {
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
        // Reprobado - mostrar opciones de recuperación
        $step = $DB->get_record_sql("
            SELECT s.* FROM {learningpath_steps} s 
            WHERE s.resourceid = ? AND s.istest = 1
            ORDER BY s.id DESC LIMIT 1
        ", [$quizid]);
        
        echo "<div style='margin-top:20px; padding:15px; background:#f8d7da; border-radius:5px;'>";
        echo "<h4>💪 Opciones para mejorar:</h4>";
        
        // Botón de reintento inmediato
        $retryurl = new moodle_url('/mod/learningstylesurvey/responder_quiz.php', [
            'id' => $quizid,
            'courseid' => $courseid,
            'embedded' => $embedded ? 1 : 0,
            'retry' => 1
        ]);
        echo "<a href='{$retryurl}' class='btn btn-primary'>🔄 Reintentar ahora</a> ";
        
        if ($step && $step->failredirect) {
            // El salto apunta a un tema ID de refuerzo
            $refuerzourl = new moodle_url('/mod/learningstylesurvey/vista_estudiante.php', [
                'courseid' => $courseid,
                'pathid' => $step->pathid,
                'tema_refuerzo' => $step->failredirect
            ]);
            echo "<a class='btn btn-warning' href='" . $refuerzourl->out() . "'>📚 Estudiar material de refuerzo</a> ";
        }
        
        echo "</div>";
        
    }
    
    // Agregar botón volver en todos los casos
    $returnurl = new moodle_url('/mod/learningstylesurvey/vista_estudiante.php', ['courseid' => $courseid]);
    echo "<div style='margin-top:15px;'>";
    echo "<a href='" . $returnurl->out() . "' class='btn btn-secondary'>Volver</a>";
    echo "</div>";
    
    echo "</div>";
} else if ($result && !$auto_retry) {
    // Mostrar resultado previo solo si está aprobado o no se permite auto-retry
    echo "<div style='text-align:center; margin-top:20px;'>";
    echo "<h3>Resultado previo: {$result->score}%</h3>";
    echo $result->score >= 70
        ? "<p style='color:green; font-weight:bold;'>¡Aprobado!</p>"
        : "<p style='color:red; font-weight:bold;'>Reprobado</p>";
    
    // Si está reprobado, mostrar opciones para mejorar
    if ($result->score < 70) {
        echo "<div style='margin-top:20px; padding:15px; background:#fff3cd; border-radius:5px;'>";
        echo "<h4>💡 Opciones para mejorar:</h4>";
        
        $retryurl = new moodle_url('/mod/learningstylesurvey/responder_quiz.php', [
            'id' => $quizid,
            'courseid' => $courseid,
            'embedded' => $embedded ? 1 : 0,
            'retry' => 1
        ]);
        echo "<a href='{$retryurl}' class='btn btn-primary'>🔄 Reintentar examen</a> ";
        
        // Buscar si hay tema de refuerzo configurado
        $step = $DB->get_record_sql("
            SELECT s.* FROM {learningpath_steps} s 
            WHERE s.resourceid = ? AND s.istest = 1
            ORDER BY s.id DESC LIMIT 1
        ", [$quizid]);
        
        if ($step && $step->failredirect) {
            $refuerzourl = new moodle_url('/mod/learningstylesurvey/vista_estudiante.php', [
                'courseid' => $courseid,
                'pathid' => $step->pathid,
                'tema_refuerzo' => $step->failredirect
            ]);
            echo "<a href='{$refuerzourl}' class='btn btn-warning'>📚 Ver material de refuerzo</a>";
        }
        
        if ($quiz = $DB->get_record('learningstylesurvey_quizzes',['id'=>$quizid]) && $quiz->recoveryquizid) {
            $url = new moodle_url('/mod/learningstylesurvey/responder_quiz.php', [
                'id' => $quiz->recoveryquizid,
                'courseid' => $courseid,
                'embedded' => $embedded ? 1 : 0
            ]);
            echo "<a class='btn btn-info' href='{$url}'>📝 Examen de Recuperación</a>";
        }
        echo "</div>";
    }
    
    if (!$embedded) {
        $volver_url = new moodle_url('/mod/learningstylesurvey/vista_estudiante.php', ['courseid' => $courseid]);
        echo "<div style='margin-top:15px;'><a href='" . $volver_url->out() . "' class='btn btn-secondary'>Volver</a></div>";
    }
    echo "</div>";
} else {
    // Mostrar formulario: cuando no hay resultado, viene con retry, o auto_retry está activo
    $questions = $DB->get_records('learningstylesurvey_questions', ['quizid' => $quizid]);
    
    if ($auto_retry) {
        echo "<div class='alert alert-info'>";
        echo "<h4>🔄 Nuevo intento</h4>";
        echo "<p>Puedes volver a realizar este examen. No hay límite de intentos.</p>";
        echo "</div>";
    }
    
    echo '<form method="post">';
    foreach ($questions as $index => $q) {
        // ✅ Ordenar opciones por ID para mantener consistencia
        $options = $DB->get_records('learningstylesurvey_options', ['questionid' => $q->id], 'id ASC');
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
