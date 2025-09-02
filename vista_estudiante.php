<?php
require_once("../../config.php");
require_once("$CFG->libdir/formslib.php");
global $DB, $USER, $PAGE, $OUTPUT;

$courseid = required_param('courseid', PARAM_INT);
$pathid = optional_param('pathid', 0, PARAM_INT);
$stepid = optional_param('stepid', 0, PARAM_INT);
$tema_salto = optional_param('tema_salto', 0, PARAM_INT); // Para saltos adaptativos por tema
$tema_refuerzo = optional_param('tema_refuerzo', 0, PARAM_INT); // Para temas de refuerzo

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
    $modinfo = get_fast_modinfo($courseid);
    $cmid = null;
    foreach ($modinfo->get_cms() as $cm) {
        if ($cm->modname === 'learningstylesurvey') {
            $cmid = $cm->id;
            break;
        }
    }
    if ($cmid) {
        $viewurl = new moodle_url('/mod/learningstylesurvey/view.php', ['id' => $cmid]);
        echo html_writer::link($viewurl, 'Regresar al menú principal', ['class' => 'btn btn-primary', 'style' => 'font-size:18px; margin-top:20px;']);
    }
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

// --- FLUJO ADAPTADO ---
echo $OUTPUT->header();
echo "<div class='container' style='max-width:900px; margin:20px auto;'>";
echo "<h2>Ruta de Aprendizaje ({$style})</h2>";

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
            mostrar_recurso($resource);
        } else {
            echo "<div class='alert alert-info'>No hay recursos de refuerzo específicos para tu estilo. Revisa el material general del tema.</div>";
        }
        
        // Buscar el examen que se reprobó para permitir reintento
        $lastquiz = $DB->get_record_sql("
            SELECT qr.*, s.* FROM {learningstylesurvey_quiz_results} qr
            JOIN {learningpath_steps} s ON s.resourceid = qr.quizid AND s.istest = 1
            WHERE qr.userid = ? AND qr.courseid = ? AND s.failredirect = ?
            ORDER BY qr.timecompleted DESC LIMIT 1
        ", [$USER->id, $courseid, $tema_refuerzo]);
        
        if ($lastquiz) {
            echo "<div style='margin-top:30px;'><h4>Después de estudiar el refuerzo, puedes volver a intentar el examen</h4>";
            $retryurl = new moodle_url('/mod/learningstylesurvey/responder_quiz.php', [
                'id' => $lastquiz->quizid,
                'courseid' => $courseid,
                'embedded' => 1,
                'retry' => 1
            ]);
            echo "<a href='{$retryurl}' class='btn btn-primary'>Reintentar examen</a></div>";
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

// Verificar si viene de un examen y si lo reprobó
$lastquiz = $DB->get_record_sql("SELECT * FROM {learningstylesurvey_quiz_results} WHERE userid = ? AND courseid = ? ORDER BY timecompleted DESC LIMIT 1", [$USER->id, $courseid]);
$show_refuerzo = false;
$tema_refuerzo_id = null;
if ($lastquiz && $lastquiz->score < 70) {
    // Buscar el paso de examen y su failredirect (que apunta a tema ID)
    $exstep = $DB->get_record_sql("
        SELECT s.* FROM {learningpath_steps} s 
        WHERE s.pathid = ? AND s.resourceid = ? AND s.istest = 1
        ORDER BY s.id DESC LIMIT 1
    ", [$pathid, $lastquiz->quizid]);
    
    if ($exstep && $exstep->failredirect) {
        // failredirect apunta directamente al ID del tema
        $tema_refuerzo_id = $exstep->failredirect;
        $show_refuerzo = true;
    }
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
        mostrar_recurso($resource);
        
        // Botón para reintentar el examen después del refuerzo
        echo "<div style='margin-top:30px;'><h4>Después de estudiar el refuerzo, puedes volver a intentar el examen</h4>";
        $retryurl = new moodle_url('/mod/learningstylesurvey/responder_quiz.php', [
            'id' => $lastquiz->quizid,
            'courseid' => $courseid,
            'embedded' => 1,
            'retry' => 1
        ]);
        echo "<a href='{$retryurl}' class='btn btn-primary'>Reintentar examen</a></div>";
    } else {
        echo "<div class='alert alert-warning'>No hay recursos de refuerzo específicos para tu estilo de aprendizaje.</div>";
    }
} else {
    // Flujo normal: mostrar recurso por estilo
    $step = $DB->get_record_sql("
        SELECT s.*
        FROM {learningpath_steps} s
        JOIN {learningstylesurvey_resources} r ON s.resourceid = r.id
        WHERE s.pathid = ? AND r.style = ? AND s.istest = 0
        ORDER BY s.stepnumber ASC
        LIMIT 1
    ", [$pathid, $style]);
    
    if ($step) {
        $resource = $DB->get_record('learningstylesurvey_resources', ['id'=>$step->resourceid]);
        if ($resource) {
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
