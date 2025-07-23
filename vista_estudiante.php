<?php
require_once("../../config.php");
require_once("$CFG->libdir/formslib.php");
global $DB, $USER, $PAGE, $OUTPUT;

$courseid = required_param('courseid', PARAM_INT);
$pathid = optional_param('pathid', 0, PARAM_INT);
$stepid = optional_param('stepid', 0, PARAM_INT);

// ✅ Obtener la ruta más reciente si no se pasa pathid
if (!$pathid) {
    $lastroute = $DB->get_record_sql("SELECT id FROM {learningstylesurvey_paths} WHERE courseid = ? ORDER BY timecreated DESC LIMIT 1", [$courseid]);
    if (!$lastroute) {
        throw new moodle_exception('No se encontró ninguna ruta para este curso.');
    }
    $pathid = $lastroute->id;
}

require_login($courseid);
$context = context_course::instance($courseid);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/mod/learningstylesurvey/vista_estudiante.php', ['courseid' => $courseid, 'pathid' => $pathid]));
$PAGE->set_title("Ruta de Aprendizaje");
$PAGE->set_heading("Ruta de Aprendizaje");

// ✅ Obtener o crear progreso
$progress = $DB->get_record('learningstylesurvey_user_progress', ['userid' => $USER->id, 'pathid' => $pathid]);

if (!$progress) {
    $firststep = $DB->get_record_sql("SELECT * FROM {learningpath_steps} WHERE pathid = ? ORDER BY stepnumber ASC LIMIT 1", [$pathid]);
    if (!$firststep) {
        throw new moodle_exception('No hay pasos definidos para esta ruta.');
    }
    $progress = (object)[
        'userid' => $USER->id,
        'pathid' => $pathid,
        'current_stepid' => $firststep->id,
        'status' => 'inprogress',
        'timemodified' => time()
    ];
    $progress->id = $DB->insert_record('learningstylesurvey_user_progress', $progress);
}

// ✅ Validar paso actual
$currentstepid = $stepid ?: $progress->current_stepid;
$currentstep = $DB->get_record('learningpath_steps', ['id' => $currentstepid]);

// ✅ Si el paso no existe, reiniciar al primer paso válido
if (!$currentstep) {
    $firststep = $DB->get_record_sql("SELECT * FROM {learningpath_steps} WHERE pathid = ? ORDER BY stepnumber ASC LIMIT 1", [$pathid]);
    if ($firststep) {
        $progress->current_stepid = $firststep->id;
        $DB->update_record('learningstylesurvey_user_progress', $progress);
        redirect(new moodle_url('/mod/learningstylesurvey/vista_estudiante.php', ['courseid' => $courseid, 'pathid' => $pathid]));
    } else {
        throw new moodle_exception('No hay pasos válidos en esta ruta.');
    }
}

// ✅ Renderizar interfaz
echo $OUTPUT->header();
echo "<div class='container' style='max-width:900px; margin:20px auto;'>";
echo "<h2>Ruta de Aprendizaje</h2>";
echo "<h3>Paso {$currentstep->stepnumber}</h3>";

// ✅ Mostrar todos los pasos filtrando recursos/eliminados
$steps = $DB->get_records('learningpath_steps', ['pathid' => $pathid], 'stepnumber ASC');

echo "<div style='margin-top:20px; border:1px solid #ddd; padding:15px; border-radius:8px; background:#f9f9f9;'>";
echo "<h4>Progreso:</h4>";

foreach ($steps as $step) {
    if ($step->istest) {
        if (!$DB->record_exists('learningstylesurvey_quizzes', ['id' => $step->resourceid])) continue;
        $nombre = $DB->get_field('learningstylesurvey_quizzes', 'name', ['id' => $step->resourceid]);
    } else {
        if (!$DB->record_exists('learningstylesurvey_resources', ['id' => $step->resourceid])) continue;
        $nombre = $DB->get_field('learningstylesurvey_resources', 'name', ['id' => $step->resourceid]);
    }

    $estado = ($step->id == $progress->current_stepid) ? 'Activo' : (($step->stepnumber < $currentstep->stepnumber) ? 'Completado' : 'Bloqueado');
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

// ✅ Render paso actual
if ($currentstep->istest) {
    $quizid = $currentstep->resourceid;
    $quiz = $DB->get_record('learningstylesurvey_quizzes', ['id' => $quizid], '*', MUST_EXIST);
    $questions = $DB->get_records('learningstylesurvey_questions', ['quizid' => $quizid]);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $answers = $_POST['answers'] ?? [];
        $correct = 0;
        $total = count($questions);

        foreach ($questions as $q) {
            $userAnswer = $answers[$q->id] ?? '';
            $correctAnswer = trim($q->correctanswer);
            if ($userAnswer == $correctAnswer) {
                $correct++;
            }
        }

        $grade = ($total > 0) ? ($correct / $total) * 100 : 0;
        $progress->grade = $grade;
        $progress->timemodified = time();

        // ✅ Control de navegación adaptativa
        if ($grade >= 70 && $currentstep->passredirect && $DB->record_exists('learningpath_steps', ['id' => $currentstep->passredirect])) {
            $progress->current_stepid = $currentstep->passredirect;
        } elseif ($grade < 70 && $currentstep->failredirect && $DB->record_exists('learningpath_steps', ['id' => $currentstep->failredirect])) {
            $progress->current_stepid = $currentstep->failredirect;
        } else {
            $nextstep = $DB->get_record_sql("SELECT id FROM {learningpath_steps} WHERE pathid = ? AND stepnumber > ? ORDER BY stepnumber ASC LIMIT 1", [$pathid, $currentstep->stepnumber]);
            $progress->current_stepid = $nextstep ? $nextstep->id : $progress->current_stepid;
            if (!$nextstep) $progress->status = 'completed';
        }

        $DB->update_record('learningstylesurvey_user_progress', $progress);
        redirect(new moodle_url('/mod/learningstylesurvey/vista_estudiante.php', ['courseid' => $courseid, 'pathid' => $pathid]));
    } else {
        echo "<h4>Examen: {$quiz->name}</h4>";
        echo "<form method='POST'>";
        foreach ($questions as $q) {
            echo "<p><strong>{$q->questiontext}</strong></p>";
            $options = $DB->get_records('learningstylesurvey_options', ['questionid' => $q->id]);
            foreach ($options as $opt) {
                echo "<label><input type='radio' name='answers[{$q->id}]' value='{$opt->optiontext}'> {$opt->optiontext}</label><br>";
            }
        }
        echo "<br><button type='submit' class='btn btn-primary'>Enviar respuestas</button>";
        echo "</form>";
    }
} else {
    $resource = $DB->get_record('learningstylesurvey_resources', ['id' => $currentstep->resourceid]);
    if ($resource) {
        $fileurl = "{$CFG->wwwroot}/mod/learningstylesurvey/uploads/{$resource->filename}";
        echo "<div><strong>Recurso:</strong></div>";
        echo "<iframe src='{$fileurl}' style='width:100%; height:600px; border:1px solid #ccc;'></iframe>";
    }
    echo "<form method='POST' action='siguiente.php'><input type='hidden' name='stepid' value='{$currentstep->id}'><input type='hidden' name='courseid' value='{$courseid}'><button type='submit' class='btn btn-success'>Continuar</button></form>";
}

// ✅ Botón regresar al menú
$cm = $DB->get_record_sql("
    SELECT cm.id FROM {course_modules} cm
    JOIN {modules} m ON m.id = cm.module
    WHERE cm.course = ? AND m.name = 'learningstylesurvey'
    ORDER BY cm.id ASC LIMIT 1", [$courseid]);

if ($cm) {
    $menuurl = new moodle_url('/mod/learningstylesurvey/view.php', ['id' => $cm->id]);
    echo "<div style='margin-top:30px;'><a href='{$menuurl}' class='btn btn-secondary'>Regresar al menú</a></div>";
}

echo "</div>";
echo $OUTPUT->footer();
?>
