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
        
        echo "<div style='margin-top:20px; text-align:center;'>";
        echo "<div class='alert alert-success' style='margin-bottom:20px;'>";
        echo "<h4>✅ ¡Excelente trabajo!</h4>";
        echo "<p>Has aprobado el examen. Tómate el tiempo necesario para revisar tu resultado.</p>";
        echo "</div>";
        
        echo "<div id='countdown-message' style='margin:20px 0; padding:15px; background:#e7f3ff; border-left:4px solid #007bff; border-radius:5px;'>";
        echo "<p><strong>🕒 Redirección automática en <span id='countdown'>30</span> segundos</strong></p>";
        echo "<p><small>Puedes continuar manualmente cuando estés listo.</small></p>";
        echo "</div>";
        
        echo "<a href='{$nexturl}' class='btn btn-success btn-lg' style='margin:10px; padding:12px 25px; font-size:16px; text-decoration:none; background:#28a745; color:white; border-radius:5px; display:inline-block;'>Continuar ahora</a>";
        echo "</div>";
        
        echo "<script>
            var timeLeft = 30;
            var countdownElement = document.getElementById('countdown');
            
            var timer = setInterval(function() {
                timeLeft--;
                countdownElement.textContent = timeLeft;
                
                if (timeLeft <= 0) {
                    clearInterval(timer);
                    window.location.href = '{$nexturl}';
                }
            }, 1000);
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
                    // ES TEMA DE REFUERZO: Mensaje con tiempo y botón
                    echo "<div class='alert alert-warning' style='text-align:center; margin-top:20px;'>";
                    echo "<h4>🔄 Material de refuerzo disponible</h4>";
                    echo "<p>Tu puntuación indica que necesitas revisar material adicional para reforzar tu comprensión del tema.</p>";
                    echo "<p><strong>Te recomendamos revisar el contenido de refuerzo antes de intentar nuevamente.</strong></p>";
                    echo "</div>";
                    
                    echo "<div id='countdown-message-refuerzo' style='margin:20px 0; padding:15px; background:#fff3cd; border-left:4px solid #ffc107; border-radius:5px;'>";
                    echo "<p><strong>🕒 Redirección automática al material de refuerzo en <span id='countdown-refuerzo'>10</span> segundos</strong></p>";
                    echo "<p><small>Puedes acceder inmediatamente si estás listo.</small></p>";
                    echo "</div>";
                    
                    $refuerzourl = new moodle_url('/mod/learningstylesurvey/path/vista_estudiante.php', [
                        'courseid' => $courseid,
                        'pathid' => $step->pathid,
                        'tema_refuerzo' => $target_resource->tema,
                        'cmid' => $cmid
                    ]);
                    
                    echo "<div style='text-align:center; margin:20px 0;'>";
                    echo "<a href='{$refuerzourl}' class='btn btn-warning btn-lg' style='margin:10px; padding:12px 25px; font-size:16px; text-decoration:none; background:#ffc107; color:#000; border-radius:5px; display:inline-block;'>Ir al material de refuerzo</a>";
                    echo "</div>";
                    
                    echo "<script>
                        var timeLeftRefuerzo = 10;
                        var countdownElementRefuerzo = document.getElementById('countdown-refuerzo');
                        
                        var timerRefuerzo = setInterval(function() {
                            timeLeftRefuerzo--;
                            countdownElementRefuerzo.textContent = timeLeftRefuerzo;
                            
                            if (timeLeftRefuerzo <= 0) {
                                clearInterval(timerRefuerzo);
                                window.location.href = '{$refuerzourl}';
                            }
                        }, 1000);
                    </script>";
                } else {
                    // NO ES TEMA DE REFUERZO: Tema asignado para revisión
                    echo "<div class='alert alert-info' style='text-align:center; margin-top:20px;'>";
                    echo "<h4>🎯 Material adicional asignado</h4>";
                    echo "<p>Se te ha asignado material adicional para complementar tu aprendizaje antes de continuar.</p>";
                    echo "<p><strong>Te recomendamos revisar este contenido antes de seguir con la ruta.</strong></p>";
                    echo "</div>";
                    
                    echo "<div id='countdown-message-salto' style='margin:20px 0; padding:15px; background:#d1ecf1; border-left:4px solid #17a2b8; border-radius:5px;'>";
                    echo "<p><strong>🕒 Redirección automática al material en <span id='countdown-salto'>10</span> segundos</strong></p>";
                    echo "<p><small>Puedes acceder inmediatamente si prefieres.</small></p>";
                    echo "</div>";
                    
                    $saltourl = new moodle_url('/mod/learningstylesurvey/path/vista_estudiante.php', [
                        'courseid' => $courseid,
                        'pathid' => $step->pathid,
                        'tema_salto' => $target_resource->tema,
                        'cmid' => $cmid
                    ]);
                    
                    echo "<div style='text-align:center; margin:20px 0;'>";
                    echo "<a href='{$saltourl}' class='btn btn-info btn-lg' style='margin:10px; padding:12px 25px; font-size:16px; text-decoration:none; background:#17a2b8; color:white; border-radius:5px; display:inline-block;'>Ir al material asignado</a>";
                    echo "</div>";
                    
                    echo "<script>
                        var timeLeftSalto = 10;
                        var countdownElementSalto = document.getElementById('countdown-salto');
                        
                        var timerSalto = setInterval(function() {
                            timeLeftSalto--;
                            countdownElementSalto.textContent = timeLeftSalto;
                            
                            if (timeLeftSalto <= 0) {
                                clearInterval(timerSalto);
                                window.location.href = '{$saltourl}';
                            }
                        }, 1000);
                    </script>";
                }
            } else {
                // Si no se encuentra el recurso de salto, permitir reintento
                echo "<div class='alert alert-info' style='text-align:center; margin-top:20px;'>";
                echo "<h4>� Preparando nuevo intento</h4>";
                echo "<p>No se encontró material adicional. Puedes intentar el examen nuevamente cuando estés listo.</p>";
                echo "</div>";
                
                echo "<div id='countdown-message-retry1' style='margin:20px 0; padding:15px; background:#e7f3ff; border-left:4px solid #007bff; border-radius:5px;'>";
                echo "<p><strong>🕒 Redirección automática para reintento en <span id='countdown-retry1'>10</span> segundos</strong></p>";
                echo "<p><small>Puedes intentar inmediatamente si estás preparado.</small></p>";
                echo "</div>";
                
                $retryurl = new moodle_url('/mod/learningstylesurvey/quiz/responder_quiz.php', [
                    'id' => $quizid,
                    'courseid' => $courseid,
                    'embedded' => 1,
                    'retry' => 1,
                    'cmid' => $cmid
                ]);
                
                echo "<div style='text-align:center; margin:20px 0;'>";
                echo "<a href='{$retryurl}' class='btn btn-primary btn-lg' style='margin:10px; padding:12px 25px; font-size:16px; text-decoration:none; background:#007bff; color:white; border-radius:5px; display:inline-block;'>Intentar nuevamente</a>";
                echo "</div>";
                
                echo "<script>
                    var timeLeftRetry1 = 10;
                    var countdownElementRetry1 = document.getElementById('countdown-retry1');
                    
                    var timerRetry1 = setInterval(function() {
                        timeLeftRetry1--;
                        countdownElementRetry1.textContent = timeLeftRetry1;
                        
                        if (timeLeftRetry1 <= 0) {
                            clearInterval(timerRetry1);
                            window.location.href = '{$retryurl}';
                        }
                    }, 1000);
                </script>";
            }
        } else {
            // No hay salto configurado - permitir reintento inmediato
            echo "<div class='alert alert-info' style='text-align:center; margin-top:20px;'>";
            echo "<h4>🔄 Nuevo intento disponible</h4>";
            echo "<p>No se ha configurado material adicional. Puedes intentar el examen nuevamente para mejorar tu puntuación.</p>";
            echo "</div>";
            
            echo "<div id='countdown-message-retry2' style='margin:20px 0; padding:15px; background:#e7f3ff; border-left:4px solid #007bff; border-radius:5px;'>";
            echo "<p><strong>🕒 Redirección automática para reintento en <span id='countdown-retry2'>10</span> segundos</strong></p>";
            echo "<p><small>Puedes comenzar cuando te sientas preparado.</small></p>";
            echo "</div>";
            
            $retryurl = new moodle_url('/mod/learningstylesurvey/quiz/responder_quiz.php', [
                'id' => $quizid,
                'courseid' => $courseid,
                'embedded' => 1,
                'retry' => 1,
                'cmid' => $cmid
            ]);
            
            echo "<div style='text-align:center; margin:20px 0;'>";
            echo "<a href='{$retryurl}' class='btn btn-primary btn-lg' style='margin:10px; padding:12px 25px; font-size:16px; text-decoration:none; background:#007bff; color:white; border-radius:5px; display:inline-block;'>Intentar nuevamente</a>";
            echo "</div>";
            
            echo "<script>
                var timeLeftRetry2 = 10;
                var countdownElementRetry2 = document.getElementById('countdown-retry2');
                
                var timerRetry2 = setInterval(function() {
                    timeLeftRetry2--;
                    countdownElementRetry2.textContent = timeLeftRetry2;
                    
                    if (timeLeftRetry2 <= 0) {
                        clearInterval(timerRetry2);
                        window.location.href = '{$retryurl}';
                    }
                }, 1000);
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
        echo "<h4>📊 Revisión de resultado</h4>";
        echo "<p>Tu puntuación anterior fue insuficiente. Revisa las opciones disponibles para mejorar tu comprensión.</p>";
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
                    // Mensaje para tema de refuerzo
                    echo "<div class='alert alert-info' style='margin-top:20px;'>";
                    echo "<h5>🔄 Material de refuerzo disponible</h5>";
                    echo "<p>Se recomienda revisar el material de refuerzo antes de intentar nuevamente.</p>";
                    echo "</div>";
                    
                    echo "<div id='countdown-message-previo-refuerzo' style='margin:20px 0; padding:15px; background:#fff3cd; border-left:4px solid #ffc107; border-radius:5px;'>";
                    echo "<p><strong>🕒 Redirección automática al refuerzo en <span id='countdown-previo-refuerzo'>10</span> segundos</strong></p>";
                    echo "</div>";
                    
                    $refuerzourl = new moodle_url('/mod/learningstylesurvey/path/vista_estudiante.php', [
                        'courseid' => $courseid,
                        'pathid' => $step->pathid,
                        'tema_refuerzo' => $target_resource->tema,
                        'cmid' => $cmid
                    ]);
                    
                    echo "<div style='text-align:center; margin:20px 0;'>";
                    echo "<a href='{$refuerzourl}' class='btn btn-warning btn-lg' style='margin:10px; padding:12px 25px; font-size:16px; text-decoration:none; background:#ffc107; color:#000; border-radius:5px; display:inline-block;'>Ir al refuerzo</a>";
                    echo "</div>";
                    
                    echo "<script>
                        var timeLeftPrevioRefuerzo = 10;
                        var countdownElementPrevioRefuerzo = document.getElementById('countdown-previo-refuerzo');
                        
                        var timerPrevioRefuerzo = setInterval(function() {
                            timeLeftPrevioRefuerzo--;
                            countdownElementPrevioRefuerzo.textContent = timeLeftPrevioRefuerzo;
                            
                            if (timeLeftPrevioRefuerzo <= 0) {
                                clearInterval(timerPrevioRefuerzo);
                                window.location.href = '{$refuerzourl}';
                            }
                        }, 1000);
                    </script>";
                } else {
                    // Redirección a tema normal (sin forzar retorno)
                    echo "<div class='alert alert-info' style='margin-top:20px;'>";
                    echo "<h5>📚 Material de apoyo disponible</h5>";
                    echo "<p>Se ha configurado material adicional para ayudarte a mejorar. Puedes revisarlo antes de reintentar.</p>";
                    echo "</div>";
                    
                    echo "<div id='countdown-message-previo-salto' style='margin:20px 0; padding:15px; background:#d1ecf1; border-left:4px solid #17a2b8; border-radius:5px;'>";
                    echo "<p><strong>🕒 Redirección automática en <span id='countdown-previo-salto'>10</span> segundos</strong></p>";
                    echo "</div>";
                    
                    $saltourl = new moodle_url('/mod/learningstylesurvey/path/vista_estudiante.php', [
                        'courseid' => $courseid,
                        'pathid' => $step->pathid,
                        'tema_salto' => $target_resource->tema,
                        'cmid' => $cmid
                    ]);
                    
                    echo "<div style='text-align:center; margin:20px 0;'>";
                    echo "<a href='{$saltourl}' class='btn btn-info btn-lg' style='margin:10px; padding:12px 25px; font-size:16px; text-decoration:none; background:#17a2b8; color:#fff; border-radius:5px; display:inline-block;'>Ver material</a>";
                    echo "</div>";
                    
                    echo "<script>
                        var timeLeftPrevioSalto = 10;
                        var countdownElementPrevioSalto = document.getElementById('countdown-previo-salto');
                        
                        var timerPrevioSalto = setInterval(function() {
                            timeLeftPrevioSalto--;
                            countdownElementPrevioSalto.textContent = timeLeftPrevioSalto;
                            
                            if (timeLeftPrevioSalto <= 0) {
                                clearInterval(timerPrevioSalto);
                                window.location.href = '{$saltourl}';
                            }
                        }, 1000);
                    </script>";
                }
            } else {
                // Si no se encuentra recurso, ir a reintento
                echo "<div class='alert alert-secondary' style='margin-top:20px;'>";
                echo "<h5>🔄 Preparando reintento</h5>";
                echo "<p>No se encontró material específico. Puedes volver a intentar cuando estés listo.</p>";
                echo "</div>";
                
                echo "<div id='countdown-message-previo-retry' style='margin:20px 0; padding:15px; background:#f8f9fa; border-left:4px solid #6c757d; border-radius:5px;'>";
                echo "<p><strong>🕒 Redirección automática al reintento en <span id='countdown-previo-retry'>10</span> segundos</strong></p>";
                echo "</div>";
                
                $retryurl = new moodle_url('/mod/learningstylesurvey/quiz/responder_quiz.php', [
                    'id' => $quizid,
                    'courseid' => $courseid,
                    'embedded' => 1,
                    'retry' => 1,
                    'cmid' => $cmid
                ]);
                
                echo "<div style='text-align:center; margin:20px 0;'>";
                echo "<a href='{$retryurl}' class='btn btn-secondary btn-lg' style='margin:10px; padding:12px 25px; font-size:16px; text-decoration:none; background:#6c757d; color:#fff; border-radius:5px; display:inline-block;'>Reintentar ahora</a>";
                echo "</div>";
                
                echo "<script>
                    var timeLeftPrevioRetry = 10;
                    var countdownElementPrevioRetry = document.getElementById('countdown-previo-retry');
                    
                    var timerPrevioRetry = setInterval(function() {
                        timeLeftPrevioRetry--;
                        countdownElementPrevioRetry.textContent = timeLeftPrevioRetry;
                        
                        if (timeLeftPrevioRetry <= 0) {
                            clearInterval(timerPrevioRetry);
                            window.location.href = '{$retryurl}';
                        }
                    }, 1000);
                </script>";
            }
        } else {
            // No hay tema de refuerzo - ir directo a reintento
            echo "<div class='alert alert-warning' style='margin-top:20px;'>";
            echo "<h5>🎯 Preparando nuevo intento</h5>";
            echo "<p>No hay material de refuerzo configurado. Puedes intentar el examen nuevamente cuando te sientas preparado.</p>";
            echo "</div>";
            
            echo "<div id='countdown-message-previo-direct' style='margin:20px 0; padding:15px; background:#fff3cd; border-left:4px solid #ffc107; border-radius:5px;'>";
            echo "<p><strong>🕒 Redirección automática al reintento en <span id='countdown-previo-direct'>10</span> segundos</strong></p>";
            echo "</div>";
            
            $retryurl = new moodle_url('/mod/learningstylesurvey/quiz/responder_quiz.php', [
                'id' => $quizid,
                'courseid' => $courseid,
                'embedded' => 1,
                'retry' => 1,
                'cmid' => $cmid
            ]);
            
            echo "<div style='text-align:center; margin:20px 0;'>";
            echo "<a href='{$retryurl}' class='btn btn-warning btn-lg' style='margin:10px; padding:12px 25px; font-size:16px; text-decoration:none; background:#ffc107; color:#000; border-radius:5px; display:inline-block;'>Reintentar examen</a>";
            echo "</div>";
            
            echo "<script>
                var timeLeftPrevioDirect = 10;
                var countdownElementPrevioDirect = document.getElementById('countdown-previo-direct');
                
                var timerPrevioDirect = setInterval(function() {
                    timeLeftPrevioDirect--;
                    countdownElementPrevioDirect.textContent = timeLeftPrevioDirect;
                    
                    if (timeLeftPrevioDirect <= 0) {
                        clearInterval(timerPrevioDirect);
                        window.location.href = '{$retryurl}';
                    }
                }, 1000);
            </script>";
        }
    } else {
        // Está aprobado - continuar con la ruta
        echo "<div class='alert alert-success' style='text-align:center; margin-top:20px;'>";
        echo "<h4>✅ Examen ya aprobado</h4>";
        echo "<p>Tu resultado anterior fue exitoso. Continuando con la ruta de aprendizaje...</p>";
        echo "</div>";
        
        echo "<div id='countdown-message-previo-aprobado' style='margin:20px 0; padding:15px; background:#d4edda; border-left:4px solid #28a745; border-radius:5px;'>";
        echo "<p><strong>🕒 Redirección automática en <span id='countdown-previo-aprobado'>10</span> segundos</strong></p>";
        echo "</div>";
        
        $returnurl = new moodle_url('/mod/learningstylesurvey/path/vista_estudiante.php', [
            'courseid' => $courseid,
            'cmid' => $cmid
        ]);
        
        echo "<div style='text-align:center; margin:20px 0;'>";
        echo "<a href='{$returnurl}' class='btn btn-success btn-lg' style='margin:10px; padding:12px 25px; font-size:16px; text-decoration:none; background:#28a745; color:#fff; border-radius:5px; display:inline-block;'>Continuar ruta</a>";
        echo "</div>";
        
        echo "<script>
            var timeLeftPrevioAprobado = 10;
            var countdownElementPrevioAprobado = document.getElementById('countdown-previo-aprobado');
            
            var timerPrevioAprobado = setInterval(function() {
                timeLeftPrevioAprobado--;
                countdownElementPrevioAprobado.textContent = timeLeftPrevioAprobado;
                
                if (timeLeftPrevioAprobado <= 0) {
                    clearInterval(timerPrevioAprobado);
                    window.location.href = '{$returnurl}';
                }
            }, 1000);
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
        echo "<h4 style='margin-bottom:15px; color:#333;'>" . format_string($q->questiontext) . "</h4>";
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
