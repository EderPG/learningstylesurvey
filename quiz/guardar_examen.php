<?php
require_once('../../../config.php');
require_login();

global $DB, $USER;

$courseid = required_param('courseid', PARAM_INT);
$quizname = required_param('quizname', PARAM_TEXT);
$questions = $_POST['questions'] ?? [];

if (empty($questions)) {
    echo "<div style='background:#f8d7da; padding:15px; border:1px solid #f5c6cb; border-radius:5px;'>";
    echo "<h3>❌ Error: No se encontraron preguntas</h3>";
    echo "<p>El formulario no envió datos de preguntas válidos.</p>";
    echo "<p><a href='javascript:history.back()'>← Regresar al formulario</a></p>";
    echo "</div>";
    exit;
}

// ✅ Verificar si hay una instancia activa del módulo en este curso
$instances = get_fast_modinfo($courseid)->get_instances_of('learningstylesurvey');
$cmid = 0;
if ($instances) {
    $firstcm = reset($instances);
    $cmid = $firstcm->id;
}

// ✅ Insertar Quiz
$quiz = new stdClass();
$quiz->userid = $USER->id;
$quiz->timecreated = time();
$quiz->courseid = $courseid;
$quiz->name = $quizname;
$quizid = $DB->insert_record('learningstylesurvey_quizzes', $quiz);

// ✅ Insertar preguntas y opciones
$validQuestionsCount = 0;
foreach ($questions as $q) {
    // Verificar si la pregunta tiene datos válidos (no usar empty() para arrays)
    $hasText = isset($q['text']) && trim($q['text']) !== '';
    $hasOptions = isset($q['options']) && is_array($q['options']) && count($q['options']) > 0;
    $hasAnswer = isset($q['answer']) && $q['answer'] !== '';

    if (!$hasText || !$hasOptions || !$hasAnswer) {
        continue; // Saltar preguntas incompletas
    }

    $options = array_filter($q['options'], fn($opt) => trim($opt) !== '');

    $question = new stdClass();
    $question->quizid = $quizid;
    $question->questiontext = trim($q['text']);
    $question->correctanswer = (int)$q['answer'];
    $question->timecreated = time();
    $question->timemodified = time();
    $questionid = $DB->insert_record('learningstylesurvey_questions', $question);

    foreach ($options as $opt) {
        $option = new stdClass();
        $option->questionid = $questionid;
        $option->optiontext = trim($opt);
        $DB->insert_record('learningstylesurvey_options', $option);
    }

    $validQuestionsCount++;
}

// ✅ Verificar que haya al menos una pregunta válida
if ($validQuestionsCount === 0) {
    echo "<div style='background:#f8d7da; padding:15px; border:1px solid #f5c6cb; border-radius:5px;'>";
    echo "<h3>❌ Error: El examen debe tener al menos una pregunta válida</h3>";
    echo "<p>Una pregunta válida debe tener:</p>";
    echo "<ul>";
    echo "<li>Texto de la pregunta</li>";
    echo "<li>Al menos una opción de respuesta</li>";
    echo "<li>Una respuesta correcta seleccionada</li>";
    echo "</ul>";
    echo "<p><a href='javascript:history.back()'>← Regresar al formulario</a></p>";
    echo "</div>";
    exit;
}

// ✅ Vincular examen a la ruta activa si existe
$path = $DB->get_record('learningstylesurvey_paths', ['courseid' => $courseid], '*', IGNORE_MISSING);

if ($path) {
    // Insertar en tabla intermedia
    $evaluation = new stdClass();
    $evaluation->pathid = $path->id;
    $evaluation->quizid = $quizid;
    $DB->insert_record('learningstylesurvey_path_evaluations', $evaluation);

    // Insertar en pasos adaptativos
    $maxstep = $DB->get_field_sql("SELECT MAX(stepnumber) FROM {learningpath_steps} WHERE pathid = ?", [$path->id]);
    $nextstep = $maxstep ? $maxstep + 1 : 1;

    $step = new stdClass();
    $step->pathid = $path->id;
    $step->stepnumber = $nextstep;
    $step->resourceid = $quizid;
    $step->istest = 1; // Es examen
    $DB->insert_record('learningpath_steps', $step);
}

// ✅ Redirección segura
if ($cmid) {
    redirect(new moodle_url('/mod/learningstylesurvey/view.php', ['id' => $cmid]), 'Evaluación creada con éxito.', 2);
} else {
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]), 'Evaluación creada con éxito.', 2);
}
?>
