<?php
require_once("../../config.php");
require_once("$CFG->libdir/formslib.php");
global $DB, $USER, $PAGE, $OUTPUT;

$courseid = required_param('courseid', PARAM_INT);
$pathid = optional_param('pathid', 0, PARAM_INT);
$stepid = optional_param('stepid', 0, PARAM_INT);
$tema_salto = optional_param('tema_salto', 0, PARAM_INT); // Para saltos adaptativos por tema
$tema_refuerzo = optional_param('tema_refuerzo', 0, PARAM_INT); // Para temas de refuerzo
$cmid = optional_param('cmid', 0, PARAM_INT);

// Si no se proporciona cmid, obtenerlo del contexto actual
if (!$cmid) {
    $modinfo = get_fast_modinfo($courseid);
    $cms = $modinfo->get_instances_of('learningstylesurvey');
    if (!empty($cms)) {
        $firstcm = reset($cms);
        $cmid = $firstcm->id;
    }
}

require_login();

// Función para mostrar un recurso
function mostrar_recurso($resource) {
    global $CFG;
    $fileurl = "{$CFG->wwwroot}/mod/learningstylesurvey/uploads/{$resource->filename}";
    $ext = pathinfo($resource->filename, PATHINFO_EXTENSION);
    
    echo "<h3>" . format_string($resource->name ?: 'Recurso') . "</h3>";
    
    if (in_array(strtolower($ext), ['jpg','jpeg','png','gif'])) {
        echo "<img src='$fileurl' style='max-width:100%; height:auto; margin-bottom:20px;'>";
    } elseif (strtolower($ext) === 'pdf') {
        echo "<iframe src='$fileurl' style='width:100%; height:600px; border:none;'></iframe>";
    } elseif (in_array(strtolower($ext), ['mp4','webm'])) {
        echo "<video controls style='width:100%; max-height:500px;'><source src='$fileurl' type='video/$ext'>Tu navegador no soporta video HTML5.</video>";
    } else {
        echo "<a href='$fileurl' target='_blank'>Descargar recurso</a>";
    }
}
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
    // Botón para regresar al menú principal del plugin
    if ($cmid) {
        $viewurl = new moodle_url('/mod/learningstylesurvey/view.php', ['id' => $cmid]);
        echo html_writer::link($viewurl, 'Regresar al menú principal', ['class' => 'btn btn-primary', 'style' => 'font-size:18px; margin-top:20px;']);
    }
    echo "</div>";
    echo $OUTPUT->footer();
    exit;
}
$style = $userstyle->style; // El estilo ya viene normalizado desde la base de datos

// Debug temporal - mostrar información del filtrado
$debug_info = optional_param('debug', 0, PARAM_INT);
if ($debug_info) {
    echo "<div class='alert alert-info'>";
    echo "<h4>Debug - Información de filtrado:</h4>";
    echo "<p><strong>Usuario:</strong> {$USER->id}</p>";
    echo "<p><strong>Estilo original:</strong> {$userstyle->style}</p>";
    echo "<p><strong>Estilo normalizado:</strong> {$style}</p>";
    echo "<p><strong>Curso:</strong> {$courseid}</p>";
    
    // Mostrar recursos disponibles
    $all_resources = $DB->get_records('learningstylesurvey_resources', ['courseid' => $courseid]);
    echo "<p><strong>Recursos totales en el curso:</strong> " . count($all_resources) . "</p>";
    
    $style_resources = $DB->get_records('learningstylesurvey_resources', [
        'courseid' => $courseid,
        'style' => $style
    ]);
    echo "<p><strong>Recursos para estilo '{$style}':</strong> " . count($style_resources) . "</p>";
    
    if ($style_resources) {
        echo "<ul>";
        foreach ($style_resources as $res) {
            echo "<li>ID: {$res->id}, Tema: {$res->tema}, Archivo: {$res->filename}</li>";
        }
        echo "</ul>";
    }
    echo "</div>";
}

// Obtener ruta más reciente si no se pasa pathid
if (!$pathid) {
    $lastroute = $DB->get_record_sql("
        SELECT id 
        FROM {learningstylesurvey_paths} 
        WHERE courseid = ? AND cmid = ?
        ORDER BY timecreated DESC LIMIT 1
    ", [$courseid, $cmid]);
    if (!$lastroute) {
        throw new moodle_exception('No se encontró ninguna ruta para esta actividad.');
    }
    $pathid = $lastroute->id;
}

// --- FLUJO ADAPTADO ---
echo $OUTPUT->header();
echo "<div class='container' style='max-width:900px; margin:20px auto;'>";
echo "<h2>Ruta de Aprendizaje (" . ucfirst($style) . ")</h2>";

// Manejar saltos adaptativos por tema
if ($tema_salto) {
    // Mostrar recursos del tema asignado por salto
    $tema = $DB->get_record('learningstylesurvey_temas', ['id' => $tema_salto]);
    if ($tema) {
        echo "<div class='alert alert-success'>Has sido dirigido al tema: <strong>" . format_string($tema->tema) . "</strong></div>";
        $recursos = $DB->get_records('learningstylesurvey_resources', [
            'tema' => $tema_salto,
            'style' => $style,
            'courseid' => $courseid
        ]);
        
        if ($recursos) {
            $resource = reset($recursos); // Tomar el primer recurso del tema
            mostrar_recurso($resource);
            
            // Botón para continuar con la ruta normal después del tema
            echo "<form method='POST' action='siguiente_tema.php'>
                    <input type='hidden' name='courseid' value='{$courseid}'>
                    <input type='hidden' name='pathid' value='{$pathid}'>
                    <input type='hidden' name='tema_actual' value='{$tema_salto}'>
                    <button type='submit' class='btn btn-success' style='margin-top:15px;'>Continuar con la ruta</button>
                  </form>";
        } else {
            echo "<div class='alert alert-warning'>No hay recursos para tu estilo de aprendizaje en este tema.</div>";
        }
        echo "</div>";
        echo $OUTPUT->footer();
        exit;
    }
}

if ($tema_refuerzo) {
    // Mostrar tema de refuerzo
    $tema = $DB->get_record('learningstylesurvey_temas', ['id' => $tema_refuerzo]);
    if ($tema) {
        echo "<div class='alert alert-warning'>Necesitas refuerzo en el tema: <strong>" . format_string($tema->tema) . "</strong></div>";
        
        // Buscar recursos de refuerzo para este tema y estilo
        $recursos_refuerzo = $DB->get_records('learningstylesurvey_resources', [
            'tema' => $tema_refuerzo,
            'style' => $style,
            'courseid' => $courseid
        ]);
        
        if ($recursos_refuerzo) {
            $resource = reset($recursos_refuerzo);
            // Mostrar título del tema de refuerzo
            echo "<div style='background:#fff3cd; border-left:4px solid #ffc107; padding:15px; margin-bottom:20px; border-radius:5px;'>";
            echo "<h3 style='margin:0; color:#856404;'>🔄 " . format_string($tema->tema) . " (Refuerzo)</h3>";
            echo "</div>";
            
            mostrar_recurso($resource);
        } else {
            echo "<div class='alert alert-info'>No hay recursos de refuerzo específicos para tu estilo. Revisa el material general del tema.</div>";
        }
        
        // Buscar el examen que se reprobó para permitir reintento
        // Buscar exámenes que tengan salto de fallo configurado hacia este tema de refuerzo
        $lastquiz = $DB->get_record_sql("
            SELECT qr.*, s.*, ls_target.stepnumber as target_step 
            FROM {learningstylesurvey_quiz_results} qr
            JOIN {learningpath_steps} s ON s.resourceid = qr.quizid AND s.istest = 1
            JOIN {learningpath_steps} ls_target ON ls_target.stepnumber = s.failredirect AND ls_target.pathid = s.pathid
            JOIN {learningstylesurvey_resources} res ON res.id = ls_target.resourceid
            WHERE qr.userid = ? AND qr.courseid = ? AND res.tema = ? AND qr.score < 70
            ORDER BY qr.timecompleted DESC LIMIT 1
        ", [$USER->id, $courseid, $tema_refuerzo]);
        
        if ($lastquiz) {
            echo "<div style='margin-top:30px; padding: 15px; background: #e7f3ff; border-left: 4px solid #007bff; border-radius: 5px;'>";
            echo "<h4 style='margin-top: 0;'>💡 ¿Listo para el reintento?</h4>";
            echo "<p>Después de estudiar el material de refuerzo, puedes volver a intentar el examen.</p>";
            $retryurl = new moodle_url('/mod/learningstylesurvey/responder_quiz.php', [
                'id' => $lastquiz->quizid,
                'courseid' => $courseid,
                'embedded' => 1,
                'retry' => 1
            ]);
            echo "<a href='{$retryurl}' class='btn btn-primary btn-lg'>🔄 Reintentar examen</a>";
            echo "</div>";
        } else {
            // Fallback: buscar cualquier examen reciente reprobado
            $fallback_quiz = $DB->get_record_sql("
                SELECT qr.*, q.name as quiz_name
                FROM {learningstylesurvey_quiz_results} qr
                JOIN {learningstylesurvey_quizzes} q ON q.id = qr.quizid
                WHERE qr.userid = ? AND qr.courseid = ? AND qr.score < 70
                ORDER BY qr.timecompleted DESC LIMIT 1
            ", [$USER->id, $courseid]);
            
            if ($fallback_quiz) {
                echo "<div style='margin-top:30px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 5px;'>";
                echo "<h4 style='margin-top: 0;'>📝 Reintento disponible</h4>";
                echo "<p>Tienes un examen reprobado que puedes volver a intentar: <strong>" . format_string($fallback_quiz->quiz_name) . "</strong></p>";
                $retryurl = new moodle_url('/mod/learningstylesurvey/responder_quiz.php', [
                    'id' => $fallback_quiz->quizid,
                    'courseid' => $courseid,
                    'embedded' => 1,
                    'retry' => 1
                ]);
                echo "<a href='{$retryurl}' class='btn btn-warning btn-lg'>🔄 Reintentar examen</a>";
                echo "</div>";
            }
        }
        
        echo "</div>";
        echo $OUTPUT->footer();
        exit;
    }
}

// Si hay stepid específico, mostrar ese recurso (flujo normal)
if ($stepid) {
    $step = $DB->get_record('learningpath_steps', ['id' => $stepid]);
    if ($step) {
        if ($step->istest) {
            // Si es un examen, redirigir a responder_quiz.php
            $quizurl = new moodle_url('/mod/learningstylesurvey/responder_quiz.php', [
                'id' => $step->resourceid,
                'courseid' => $courseid,
                'embedded' => 1
            ]);
            redirect($quizurl);
        } else {
            // Si es un recurso, mostrarlo
            $resource = $DB->get_record('learningstylesurvey_resources', ['id' => $step->resourceid]);
            if ($resource) {
                // Mostrar título del tema actual
                $tema_actual = $DB->get_record('learningstylesurvey_temas', ['id' => $resource->tema]);
                if ($tema_actual) {
                    echo "<div style='background:#e7f3ff; border-left:4px solid #007bff; padding:15px; margin-bottom:20px; border-radius:5px;'>";
                    echo "<h3 style='margin:0; color:#0056b3;'>📚 " . format_string($tema_actual->tema) . "</h3>";
                    echo "</div>";
                }
                
                mostrar_recurso($resource);
                echo "<form method='POST' action='siguiente.php'>
                        <input type='hidden' name='courseid' value='{$courseid}'>
                        <input type='hidden' name='pathid' value='{$pathid}'>
                        <input type='hidden' name='stepid' value='{$step->id}'>
                        <button type='submit' class='btn btn-success' style='margin-top:15px;'>Continuar</button>
                      </form>";
                echo "</div>";
                echo $OUTPUT->footer();
                exit;
            }
        }
    }
}

// Verificar si el ÚLTIMO intento de un examen de ESTA RUTA fue reprobado
$lastquiz = $DB->get_record_sql("
    SELECT qr.*, s.failredirect 
    FROM {learningstylesurvey_quiz_results} qr
    JOIN {learningpath_steps} s ON s.resourceid = qr.quizid AND s.istest = 1
    WHERE qr.userid = ? AND qr.courseid = ? AND s.pathid = ?
    ORDER BY qr.timecompleted DESC 
    LIMIT 1
", [$USER->id, $courseid, $pathid]);

$show_refuerzo = false;
$tema_refuerzo_id = null;
if ($lastquiz && $lastquiz->score < 70 && $lastquiz->failredirect) {
    // Solo mostrar refuerzo si realmente reprobó Y hay un tema de refuerzo configurado
    $tema_refuerzo_id = $lastquiz->failredirect;
    $show_refuerzo = true;
}

// Debug temporal - mostrar información del examen
if ($debug_info) {
    echo "<div class='alert alert-warning'>";
    echo "<h4>Debug - Estado del examen:</h4>";
    if ($lastquiz) {
        echo "<p><strong>Último resultado:</strong> Score {$lastquiz->score}, Quiz ID {$lastquiz->quizid}, Tiempo: " . date('Y-m-d H:i:s', $lastquiz->timecompleted) . "</p>";
        echo "<p><strong>Failredirect:</strong> {$lastquiz->failredirect}</p>";
        echo "<p><strong>Mostrar refuerzo:</strong> " . ($show_refuerzo ? 'SÍ' : 'NO') . "</p>";
    } else {
        echo "<p><strong>No hay resultados de examen para esta ruta</strong></p>";
    }
    echo "</div>";
}

if ($show_refuerzo && $tema_refuerzo_id) {
    // Buscar recursos del tema de refuerzo para el estilo del usuario
    $recursos_refuerzo = $DB->get_records('learningstylesurvey_resources', [
        'tema' => $tema_refuerzo_id,
        'style' => $style,
        'courseid' => $courseid
    ]);
    
    if ($recursos_refuerzo) {
        $resource = reset($recursos_refuerzo); // Tomar el primer recurso
        $tema = $DB->get_record('learningstylesurvey_temas', ['id' => $tema_refuerzo_id]);
        
        echo "<div class='alert alert-warning' style='margin-bottom:20px;'>Has reprobado el examen, accede al tema de refuerzo: <strong>" . format_string($tema->tema) . "</strong></div>";
        
        // Mostrar título del tema de refuerzo
        echo "<div style='background:#fff3cd; border-left:4px solid #ffc107; padding:15px; margin-bottom:20px; border-radius:5px;'>";
        echo "<h3 style='margin:0; color:#856404;'>🔄 " . format_string($tema->tema) . " (Refuerzo)</h3>";
        echo "</div>";
        
        mostrar_recurso($resource);
        
        // Botón para reintentar el examen después del refuerzo
        echo "<div style='margin-top:30px; padding: 15px; background: #e7f3ff; border-left: 4px solid #007bff; border-radius: 5px;'>";
        echo "<h4 style='margin-top: 0;'>💡 ¿Listo para el reintento?</h4>";
        echo "<p>Después de estudiar el material de refuerzo, puedes volver a intentar el examen.</p>";
        $retryurl = new moodle_url('/mod/learningstylesurvey/responder_quiz.php', [
            'id' => $lastquiz->quizid,
            'courseid' => $courseid,
            'embedded' => 1,
            'retry' => 1
        ]);
        echo "<a href='{$retryurl}' class='btn btn-primary btn-lg'>🔄 Reintentar examen</a>";
        echo "</div>";
    } else {
        echo "<div class='alert alert-warning'>No hay recursos de refuerzo específicos para tu estilo de aprendizaje.</div>";
    }
} else {
    // Flujo normal: consultar progreso del usuario para mostrar el paso correcto
    $progress = $DB->get_record('learningstylesurvey_user_progress', [
        'userid' => $USER->id,
        'pathid' => $pathid
    ]);
    
    if ($progress && $progress->current_stepid) {
        // Si hay progreso, mostrar el paso actual del usuario
        $step = $DB->get_record('learningpath_steps', ['id' => $progress->current_stepid]);
        
        // Verificar que el step existe y coincide con el estilo del usuario
        if ($step && !$step->istest) {
            $resource_check = $DB->get_record('learningstylesurvey_resources', [
                'id' => $step->resourceid,
                'style' => $style
            ]);
            
            // Verificar que el tema no sea de refuerzo
            if ($resource_check) {
                $tema_check = $DB->get_record('learningstylesurvey_path_temas', [
                    'pathid' => $pathid,
                    'temaid' => $resource_check->tema,
                    'isrefuerzo' => 0  // Solo temas normales
                ]);
                if (!$tema_check) {
                    // Si el tema es de refuerzo, buscar el siguiente paso apropiado
                    $resource_check = null;
                }
            }
            
            if (!$resource_check) {
                // Si el paso actual no coincide con el estilo o es refuerzo, buscar el siguiente paso apropiado
                $step = $DB->get_record_sql("
                    SELECT s.*
                    FROM {learningpath_steps} s
                    JOIN {learningstylesurvey_resources} r ON s.resourceid = r.id
                    JOIN {learningstylesurvey_path_temas} pt ON pt.temaid = r.tema AND pt.pathid = s.pathid
                    WHERE s.pathid = ? AND r.style = ? AND s.istest = 0 AND s.stepnumber > ? AND pt.isrefuerzo = 0
                    ORDER BY s.stepnumber ASC
                    LIMIT 1
                ", [$pathid, $style, $step->stepnumber]);
            }
        }
    } else {
        // Si no hay progreso, crear uno y mostrar el primer recurso (excluyendo temas de refuerzo)
        $step = $DB->get_record_sql("
            SELECT s.*
            FROM {learningpath_steps} s
            JOIN {learningstylesurvey_resources} r ON s.resourceid = r.id
            JOIN {learningstylesurvey_path_temas} pt ON pt.temaid = r.tema AND pt.pathid = s.pathid
            WHERE s.pathid = ? AND r.style = ? AND s.istest = 0 AND pt.isrefuerzo = 0
            ORDER BY s.stepnumber ASC
            LIMIT 1
        ", [$pathid, $style]);
        
        if ($step) {
            // Crear registro de progreso
            $new_progress = (object)[
                'userid' => $USER->id,
                'pathid' => $pathid,
                'current_stepid' => $step->id,
                'status' => 'inprogress',
                'timemodified' => time()
            ];
            $DB->insert_record('learningstylesurvey_user_progress', $new_progress);
        }
    }
    
    if ($step) {
        $resource = $DB->get_record('learningstylesurvey_resources', ['id' => $step->resourceid]);
        if ($resource) {
            // Mostrar título del tema actual
            $tema_actual = $DB->get_record('learningstylesurvey_temas', ['id' => $resource->tema]);
            if ($tema_actual) {
                echo "<div style='background:#e7f3ff; border-left:4px solid #007bff; padding:15px; margin-bottom:20px; border-radius:5px;'>";
                echo "<h3 style='margin:0; color:#0056b3;'>📚 " . format_string($tema_actual->tema) . "</h3>";
                echo "</div>";
            }
            
            mostrar_recurso($resource);
            
            // Botón de avance manual
            echo "<form method='POST' action='siguiente.php'>
                    <input type='hidden' name='courseid' value='{$courseid}'>
                    <input type='hidden' name='pathid' value='{$pathid}'>
                    <input type='hidden' name='stepid' value='{$step->id}'>
                    <button type='submit' class='btn btn-success' style='margin-top:15px;'>Continuar</button>
                  </form>";
        }
    } else {
        echo "<div class='alert alert-info'>No hay recursos para tu estilo en esta ruta.</div>";
    }
}

// Buscar el primer examen programado en la ruta
$quizstep = $DB->get_record_sql("
    SELECT s.*
    FROM {learningpath_steps} s
    WHERE s.pathid = ? AND s.istest = 1
    ORDER BY s.stepnumber ASC
    LIMIT 1
", [$pathid]);
if ($quizstep && $cmid) {
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
if ($cmid) {
    $menuurl = new moodle_url('/mod/learningstylesurvey/view.php', ['id'=>$cmid]);
    echo "<div style='margin-top:30px;'><a href='{$menuurl}' class='btn btn-secondary'>Regresar al menú</a></div>";
}

echo "</div>";
echo $OUTPUT->footer();
?>
