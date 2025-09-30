<?php
require_once('../../../config.php');
global $DB, $USER, $OUTPUT;

// Detectar si se carga embebido y de dónde viene
$embedded = optional_param('embedded', 0, PARAM_INT) == 1;
$retry = optional_param('retry', 0, PARAM_INT) == 1;
$from_refuerzo = optional_param('from_refuerzo', 0, PARAM_INT) == 1;
$cmid = optional_param('cmid', 0, PARAM_INT);

$quizid   = required_param('id', PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$userid   = $USER->id;

// Validar courseid
if (!$courseid) {
    // Intentar obtener courseid desde el quiz
    $quiz = $DB->get_record('learningstylesurvey_quizzes', ['id' => $quizid]);
    if ($quiz && $quiz->courseid) {
        $courseid = $quiz->courseid;
    } else {
        throw new moodle_exception('courseid es requerido');
    }
}

require_login($courseid);
$PAGE->set_url(new moodle_url('/mod/learningstylesurvey/quiz/responder_quiz.php', ['id' => $quizid, 'courseid' => $courseid, 'embedded' => $embedded ? 1 : 0]));
$PAGE->set_context(context_course::instance($courseid));
$PAGE->set_title('Responder Cuestionario');
$PAGE->set_heading('Responder Cuestionario');
$PAGE->set_pagelayout('popup'); // Cambiar a popup para evitar problemas de navegación
echo $OUTPUT->header();
echo "<div class='box generalbox' style='padding: 20px; max-width: 800px; margin: 0 auto;'>";
echo "<h3 style='text-align: center; margin-bottom: 20px;'>Cuestionario: " . format_string($DB->get_field('learningstylesurvey_quizzes','name',['id'=>$quizid])) . "</h3>";
// Solo mostrar el cuestionario sin botones adicionales que puedan interferir

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
        
        // Verificación robusta para correctanswer (maneja tanto índice numérico como texto)
        $isCorrect = false;
        if (is_numeric($q->correctanswer)) {
            // Nuevo formato: índice numérico (0, 1, 2, 3...)
            $isCorrect = ($selectedIndex !== null && (int)$q->correctanswer == $selectedIndex);
        } else {
            // Formato antiguo: texto de la opción
            $isCorrect = ($selectedText !== null && trim(strtolower($selectedText)) === trim(strtolower($q->correctanswer)));
        }
        
        if ($isCorrect) {
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

    // CAMBIO IMPORTANTE: Siempre crear un nuevo registro para cada intento
    // Esto permite un seguimiento preciso del progreso del estudiante
    $result_id = $DB->insert_record('learningstylesurvey_quiz_results', $record);
    
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

// Lógica mejorada: verificar si hay saltos configurados antes de permitir reintentos automáticos
$can_retry = false;
$auto_retry = false;

// Solo permitir reintento automático si NO hay saltos configurados
if ($result && $result->score < 70) {
    // Verificar si hay salto configurado para este examen
    $step_check = $DB->get_record_sql("
        SELECT s.* FROM {learningpath_steps} s 
        WHERE s.resourceid = ? AND s.istest = 1
        ORDER BY s.id DESC LIMIT 1
    ", [$result->quizid]);
    
    if ($step_check && $step_check->failredirect) {
        // HAY salto configurado - NO hacer reintento automático
        echo "<div class='alert alert-warning'>⚠️ Resultado anterior reprobatorio ({$result->score}%). Se aplicará el salto programado.</div>";
        $auto_retry = false;
        $can_retry = false;
    } else {
        // NO hay salto configurado - permitir reintento automático
        echo "<div class='alert alert-warning'>⚠️ Resultado anterior reprobatorio ({$result->score}%). Puedes volver a intentarlo.</div>";
        echo "<div class='alert alert-info'>💡 <strong>Tip:</strong> Si repruebas, podrás acceder a material de refuerzo y volver a intentarlo.</div>";
        $auto_retry = true;
        $can_retry = true;
    }
}

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
        
        // REDIRECCIÓN AUTOMÁTICA después de aprobar
        echo "<div class='alert alert-success' style='text-align:center; margin-top:20px;'>";
        echo "<h4>🎉 ¡Examen aprobado!</h4>";
        echo "<p>Continuando automáticamente con la ruta de aprendizaje...</p>";
        echo "<div class='progress' style='height:15px; margin:20px 0;'>";
        echo "<div class='progress-bar progress-bar-striped progress-bar-animated' style='width:100%; background:#28a745;'></div>";
        echo "</div>";
        echo "</div>";
        
        // Buscar el paso de examen correcto para obtener saltos programados
        $step = $DB->get_record_sql("
            SELECT s.* FROM {learningpath_steps} s 
            WHERE s.resourceid = ? AND s.istest = 1
            ORDER BY s.id DESC LIMIT 1
        ", [$quizid]);
        
        if ($step && $step->passredirect) {
            // Salto programado después de aprobar
            $target_resource = $DB->get_record('learningstylesurvey_resources', ['id' => $step->passredirect]);
            
            if ($target_resource) {
                $nexturl = new moodle_url('/mod/learningstylesurvey/path/vista_estudiante.php', [
                    'courseid' => $courseid,
                    'pathid' => $step->pathid,
                    'tema_salto' => $target_resource->tema,
                    'cmid' => $cmid
                ]);
            } else {
                // Si no se encuentra el recurso, continuar con la ruta normal
                $nexturl = new moodle_url('/mod/learningstylesurvey/path/vista_estudiante.php', [
                    'courseid' => $courseid,
                    'pathid' => $step->pathid,
                    'cmid' => $cmid
                ]);
            }
        } else {
            // Si no hay salto configurado, continuar con la ruta normal
            if ($step) {
                $nexturl = new moodle_url('/mod/learningstylesurvey/path/vista_estudiante.php', [
                    'courseid' => $courseid,
                    'pathid' => $step->pathid,
                    'cmid' => $cmid
                ]);
            } else {
                // Fallback al menú principal
                $nexturl = new moodle_url('/mod/learningstylesurvey/view.php', ['id' => $cmid]);
            }
        }
        
        echo "<script>
            // Auto-redireccionar después de 9 segundos
            setTimeout(function() {
                window.location.href = '{$nexturl}';
            }, 9000);
        </script>";
        
    } else {
        echo "<p style='color:red; font-weight:bold;'>Reprobado</p>";
        
        // VERIFICAR si hay salto configurado y si es tema de refuerzo o no
        $step = $DB->get_record_sql("
            SELECT s.* FROM {learningpath_steps} s 
            WHERE s.resourceid = ? AND s.istest = 1
            ORDER BY s.id DESC LIMIT 1
        ", [$quizid]);
        
        if ($step && $step->failredirect) {
            $target_resource = $DB->get_record('learningstylesurvey_resources', ['id' => $step->failredirect]);
            
            if ($target_resource) {
                // Verificar si es tema de refuerzo
                $is_refuerzo_tema = $DB->get_record('learningstylesurvey_path_temas', [
                    'pathid' => $step->pathid,
                    'temaid' => $target_resource->tema,
                    'isrefuerzo' => 1
                ]);
                
                if ($is_refuerzo_tema) {
                    // ES TEMA DE REFUERZO: Redirección automática
                    echo "<div class='alert alert-warning' style='text-align:center; margin-top:20px;'>";
                    echo "<h4>🔄 Accediendo al material de refuerzo...</h4>";
                    echo "<p>Serás redirigido automáticamente al tema de refuerzo para mejorar tu comprensión.</p>";
                    echo "<div class='progress' style='height:15px; margin:20px 0;'>";
                    echo "<div class='progress-bar progress-bar-striped progress-bar-animated' style='width:100%; background:#ffc107;'></div>";
                    echo "</div>";
                    echo "</div>";
                    
                    $refuerzourl = new moodle_url('/mod/learningstylesurvey/path/vista_estudiante.php', [
                        'courseid' => $courseid,
                        'pathid' => $step->pathid,
                        'tema_refuerzo' => $target_resource->tema,
                        'cmid' => $cmid
                    ]);
                    
                    // Esperar 2 segundos y luego redireccionar usando PHP
                    sleep(2);
                    redirect($refuerzourl);
                    exit(); // Asegurar que no se ejecute más código
                } else {
                    // NO ES TEMA DE REFUERZO: Continuar normalmente sin forzar retorno
                    echo "<div class='alert alert-info' style='text-align:center; margin-top:20px;'>";
                    echo "<h4>🎯 Dirigiendo a tema asignado...</h4>";
                    echo "<p>Continuarás con el tema programado y seguirás la ruta normal.</p>";
                    echo "<div class='progress' style='height:15px; margin:20px 0;'>";
                    echo "<div class='progress-bar progress-bar-striped progress-bar-animated' style='width:100%; background:#17a2b8;'></div>";
                    echo "</div>";
                    echo "</div>";
                    
                    $saltourl = new moodle_url('/mod/learningstylesurvey/path/vista_estudiante.php', [
                        'courseid' => $courseid,
                        'pathid' => $step->pathid,
                        'tema_salto' => $target_resource->tema,
                        'cmid' => $cmid
                    ]);
                    
                    // Esperar 2 segundos y luego redireccionar usando PHP
                    sleep(2);
                    redirect($saltourl);
                    exit(); // Asegurar que no se ejecute más código
                }
            } else {
                // Si no se encuentra el recurso de salto, permitir reintento
                echo "<div class='alert alert-info'>💡 Puedes volver a intentarlo inmediatamente.</div>";
                
                $retryurl = new moodle_url('/mod/learningstylesurvey/quiz/responder_quiz.php', [
                    'id' => $quizid,
                    'courseid' => $courseid,
                    'embedded' => 1,
                    'retry' => 1,
                    'cmid' => $cmid
                ]);
                
                echo "<script>
                    setTimeout(function() {
                        window.location.href = '{$retryurl}';
                    }, 9000);
                </script>";
            }
        } else {
            // No hay salto configurado - permitir reintento inmediato
            echo "<div class='alert alert-info' style='text-align:center; margin-top:20px;'>";
            echo "<h4>🔄 Preparando reintento...</h4>";
            echo "<p>Puedes volver a intentar el examen inmediatamente.</p>";
            echo "<div class='progress' style='height:15px; margin:20px 0;'>";
            echo "<div class='progress-bar progress-bar-striped progress-bar-animated' style='width:100%; background:#17a2b8;'></div>";
            echo "</div>";
            echo "</div>";
            
            $retryurl = new moodle_url('/mod/learningstylesurvey/quiz/responder_quiz.php', [
                'id' => $quizid,
                'courseid' => $courseid,
                'embedded' => 1,
                'retry' => 1,
                'cmid' => $cmid
            ]);
            
            echo "<script>
                setTimeout(function() {
                    window.location.href = '{$retryurl}';
                }, 9000);
            </script>";
        }
    }
    
    // ELIMINAMOS los botones manuales - todo es automático ahora
    
    echo "</div>";
} else if ($result && !$auto_retry) {
    // Mostrar resultado previo solo si está aprobado o no se permite auto-retry
    echo "<div style='text-align:center; margin-top:20px;'>";
    echo "<h3>Resultado previo: {$result->score}%</h3>";
    echo $result->score >= 70
        ? "<p style='color:green; font-weight:bold;'>¡Aprobado!</p>"
        : "<p style='color:red; font-weight:bold;'>Reprobado</p>";
    
    // Si está reprobado, aplicar redirección automática
    if ($result->score < 70) {
        echo "<div class='alert alert-warning' style='text-align:center; margin-top:20px;'>";
        echo "<h4>� Resultado insuficiente</h4>";
        echo "<p>Serás redirigido automáticamente para mejorar tu resultado.</p>";
        echo "<div class='progress' style='height:15px; margin:20px 0;'>";
        echo "<div class='progress-bar progress-bar-striped progress-bar-animated' style='width:100%; background:#ffc107;'></div>";
        echo "</div>";
        echo "</div>";
        
        // Buscar si hay tema de refuerzo configurado
        $step = $DB->get_record_sql("
            SELECT s.* FROM {learningpath_steps} s 
            WHERE s.resourceid = ? AND s.istest = 1
            ORDER BY s.id DESC LIMIT 1
        ", [$quizid]);
        
        if ($step && $step->failredirect) {
            $target_resource = $DB->get_record('learningstylesurvey_resources', ['id' => $step->failredirect]);
            
            if ($target_resource) {
                // Verificar si es tema de refuerzo
                $is_refuerzo_tema = $DB->get_record('learningstylesurvey_path_temas', [
                    'pathid' => $step->pathid,
                    'temaid' => $target_resource->tema,
                    'isrefuerzo' => 1
                ]);
                
                if ($is_refuerzo_tema) {
                    // Redirección automática a tema de refuerzo
                    $refuerzourl = new moodle_url('/mod/learningstylesurvey/path/vista_estudiante.php', [
                        'courseid' => $courseid,
                        'pathid' => $step->pathid,
                        'tema_refuerzo' => $target_resource->tema,
                        'cmid' => $cmid
                    ]);
                    
                    echo "<script>
                        setTimeout(function() {
                            window.location.href = '{$refuerzourl}';
                        }, 9000);
                    </script>";
                } else {
                    // Redirección a tema normal (sin forzar retorno)
                    $saltourl = new moodle_url('/mod/learningstylesurvey/path/vista_estudiante.php', [
                        'courseid' => $courseid,
                        'pathid' => $step->pathid,
                        'tema_salto' => $target_resource->tema,
                        'cmid' => $cmid
                    ]);
                    
                    echo "<script>
                        setTimeout(function() {
                            window.location.href = '{$saltourl}';
                        }, 9000);
                    </script>";
                }
            } else {
                // Si no se encuentra recurso, ir a reintento
                $retryurl = new moodle_url('/mod/learningstylesurvey/quiz/responder_quiz.php', [
                    'id' => $quizid,
                    'courseid' => $courseid,
                    'embedded' => 1,
                    'retry' => 1,
                    'cmid' => $cmid
                ]);
                
                echo "<script>
                    setTimeout(function() {
                        window.location.href = '{$retryurl}';
                    }, 9000);
                </script>";
            }
        } else {
            // No hay tema de refuerzo - ir directo a reintento
            $retryurl = new moodle_url('/mod/learningstylesurvey/quiz/responder_quiz.php', [
                'id' => $quizid,
                'courseid' => $courseid,
                'embedded' => 1,
                'retry' => 1,
                'cmid' => $cmid
            ]);
            
            echo "<script>
                setTimeout(function() {
                    window.location.href = '{$retryurl}';
                }, 9000);
            </script>";
        }
    } else {
        // Está aprobado - continuar con la ruta
        echo "<div class='alert alert-success' style='text-align:center; margin-top:20px;'>";
        echo "<h4>✅ Examen ya aprobado</h4>";
        echo "<p>Continuando con la ruta de aprendizaje...</p>";
        echo "<div class='progress' style='height:15px; margin:20px 0;'>";
        echo "<div class='progress-bar progress-bar-striped progress-bar-animated' style='width:100%; background:#28a745;'></div>";
        echo "</div>";
        echo "</div>";
        
        $returnurl = new moodle_url('/mod/learningstylesurvey/path/vista_estudiante.php', [
            'courseid' => $courseid,
            'cmid' => $cmid
        ]);
        
        echo "<script>
            setTimeout(function() {
                window.location.href = '{$returnurl}';
            }, 9000);
        </script>";
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
    
    echo '<form method="post" action="">'; 
    echo '<div style="margin: 20px 0;">';
    foreach ($questions as $index => $q) {
        // ✅ Ordenar opciones por ID para mantener consistencia
        $options = $DB->get_records('learningstylesurvey_options', ['questionid' => $q->id], 'id ASC');
        echo "<div style='margin-bottom:25px; padding:15px; border:1px solid #ddd; border-radius:5px; background:#f9f9f9;'>";
        echo "<h4 style='margin-bottom:15px; color:#333;'>" . ($index + 1) . ". " . format_string($q->questiontext) . "</h4>";
        foreach ($options as $opt) {
            $radio_id = "q{$q->id}_opt{$opt->id}";
            echo "<div style='margin-bottom:10px;'>";
            echo "<input type='radio' id='{$radio_id}' name='question{$q->id}' value='{$opt->id}' style='margin-right:8px;'>";
            echo "<label for='{$radio_id}' style='cursor:pointer;'>" . format_string($opt->optiontext) . "</label>";
            echo "</div>";
        }
        echo "</div>";
    }
    echo '<div style="text-align:center; margin-top:30px;">
            <input type="submit" value="Enviar respuestas" style="padding:12px 30px; font-size:16px; background:#007bff; color:white; border:none; border-radius:5px; cursor:pointer;">
          </div>';
    echo '</div>';
    echo '</form>';
}

echo "</div>";
?>
