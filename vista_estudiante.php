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
if (!$userstyle) {
    throw new moodle_exception('No tienes un estilo de aprendizaje registrado. Por favor, realiza la encuesta.');
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

// Obtener o crear progreso
$progress = $DB->get_record('learningstylesurvey_user_progress', ['userid'=>$USER->id,'pathid'=>$pathid]);
if (!$progress) {
    $firststep = $DB->get_record_sql("
        SELECT *
        FROM {learningpath_steps}
        WHERE pathid = ?
        ORDER BY stepnumber ASC
        LIMIT 1
    ", [$pathid]);
    if (!$firststep) throw new moodle_exception('No hay pasos definidos en esta ruta.');

    $progress = (object)[
        'userid' => $USER->id,
        'pathid' => $pathid,
        'current_stepid' => $firststep->id,
        'status' => 'inprogress',
        'timemodified' => time()
    ];
    $progress->id = $DB->insert_record('learningstylesurvey_user_progress', $progress);
}

// Validar paso actual
$currentstepid = $stepid ?: $progress->current_stepid;
$currentstep = $DB->get_record('learningpath_steps', ['id'=>$currentstepid]);
if (!$currentstep) {
    $firststep = $DB->get_record_sql("
        SELECT *
        FROM {learningpath_steps}
        WHERE pathid = ?
        ORDER BY stepnumber ASC
        LIMIT 1
    ", [$pathid]);
    if ($firststep) {
        $progress->current_stepid = $firststep->id;
        $DB->update_record('learningstylesurvey_user_progress', $progress);
        redirect(new moodle_url('/mod/learningstylesurvey/vista_estudiante.php', ['courseid'=>$courseid,'pathid'=>$pathid]));
    } else throw new moodle_exception('No hay pasos válidos en esta ruta.');
}

// Renderizar interfaz
echo $OUTPUT->header();
echo "<div class='container' style='max-width:900px; margin:20px auto;'>";
echo "<h2>Ruta de Aprendizaje ({$style})</h2>";
echo "<h3>Paso {$currentstep->stepnumber}</h3>";

// Obtener todos los pasos de la ruta para mostrar progreso
$steps = $DB->get_records_sql("
    SELECT *
    FROM {learningpath_steps}
    WHERE pathid = ?
    ORDER BY stepnumber ASC
", [$pathid]);

echo "<div style='margin-top:20px; border:1px solid #ddd; padding:15px; border-radius:8px; background:#f9f9f9;'>";
echo "<h4>Progreso:</h4>";
foreach ($steps as $step) {
    $nombre = ($step->istest == 1) 
        ? $DB->get_field('learningstylesurvey_quizzes','name',['id'=>$step->resourceid])
        : $DB->get_field('learningstylesurvey_resources','name',['id'=>$step->resourceid]);
    
    $estado = ($step->id == $progress->current_stepid) 
        ? 'Activo' 
        : (($step->stepnumber < $currentstep->stepnumber) ? 'Completado' : 'Bloqueado');

    echo "<div style='margin-bottom:8px;'>";
    echo "<strong>Paso {$step->stepnumber}:</strong> " . format_string($nombre) . " <em>({$estado})</em>";
    if ($step->id == $progress->current_stepid) {
        echo " <a href='?courseid={$courseid}&pathid={$pathid}&stepid={$step->id}' class='btn btn-sm btn-primary'>Ir</a>";
    } else {
        echo " <button class='btn btn-sm btn-secondary' disabled>Bloqueado</button>";
    }
    echo "</div>";
}
echo "</div><hr>";

// Renderizar paso actual
$can_continue = true;

if ($currentstep->istest == 1) {
    $quizid = $currentstep->resourceid;

    // Eliminar resultados previos para que el examen sea limpio
    $DB->delete_records('learningstylesurvey_quiz_results', [
        'quizid'=>$quizid,
        'userid'=>$USER->id,
        'courseid'=>$courseid
    ]);

    // Incluir examen embebido
    $_GET['embedded'] = 1;
    $_GET['id'] = $quizid;
    $_GET['courseid'] = $courseid;
    include('responder_quiz.php');

    // Revisar resultado si existe
    $result = $DB->get_record('learningstylesurvey_quiz_results', [
        'quizid'=>$quizid,
        'userid'=>$USER->id,
        'courseid'=>$courseid
    ]);

    if ($result) {
        if ($result->score >= 70 && $currentstep->passredirect) {
            $progress->current_stepid = $currentstep->passredirect;
            $DB->update_record('learningstylesurvey_user_progress', $progress);
            redirect(new moodle_url('/mod/learningstylesurvey/vista_estudiante.php', ['courseid'=>$courseid,'pathid'=>$pathid]));
        } elseif ($result->score < 70 && $currentstep->failredirect) {
            $progress->current_stepid = $currentstep->failredirect;
            $DB->update_record('learningstylesurvey_user_progress', $progress);
            redirect(new moodle_url('/mod/learningstylesurvey/vista_estudiante.php', ['courseid'=>$courseid,'pathid'=>$pathid]));
        } elseif ($result->score < 70) {
            $can_continue = false; // Bloquear continuar si no aprobó y no hay failredirect
        }
    } else {
        $can_continue = false;
    }

} else {
    $resource = $DB->get_record('learningstylesurvey_resources',['id'=>$currentstep->resourceid]);
    if ($resource) {
        $fileurl = "{$CFG->wwwroot}/mod/learningstylesurvey/uploads/{$resource->filename}";
        echo "<div><strong>Recurso:</strong></div>";
        echo "<iframe src='{$fileurl}' style='width:100%; height:600px; border:1px solid #ccc;'></iframe>";
    }
}

// Botón continuar al siguiente paso solo si no es examen o ya pasó el examen
if ($can_continue) {
    echo "<form method='POST' action='siguiente.php'>
            <input type='hidden' name='stepid' value='{$progress->current_stepid}'>
            <input type='hidden' name='courseid' value='{$courseid}'>
            <button type='submit' class='btn btn-success' style='margin-top:15px;'>Continuar</button>
          </form>";
} else {
    echo "<div style='margin-top:15px; color:red; font-weight:bold;'>Debes aprobar el examen para poder continuar</div>";
}

// Botón regresar al menú
$cm = $DB->get_record_sql("
    SELECT cm.id 
    FROM {course_modules} cm
    JOIN {modules} m ON m.id = cm.module
    WHERE cm.course = ? AND m.name = 'learningstylesurvey'
    ORDER BY cm.id ASC LIMIT 1", [$courseid]);
if ($cm) {
    $menuurl = new moodle_url('/mod/learningstylesurvey/view.php', ['id'=>$cm->id]);
    echo "<div style='margin-top:30px;'><a href='{$menuurl}' class='btn btn-secondary'>Regresar al menú</a></div>";
}

echo "</div>";
echo $OUTPUT->footer();
?>
