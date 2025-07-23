<?php
require_once('../../config.php');
require_login();

global $DB, $USER;

$courseid = required_param('courseid', PARAM_INT);
$quizname = required_param('quizname', PARAM_TEXT);
$questions = $_POST['questions'] ?? [];

if (empty($questions)) {
    print_error('Debe agregar al menos una pregunta.');
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
foreach ($questions as $q) {
    if (empty($q['text']) || empty($q['options']) || !isset($q['answer'])) {
        continue; // Saltar preguntas incompletas
    }

    $options = array_filter($q['options'], fn($opt) => trim($opt) !== '');
    if (count($options) < 2) {
        continue; // Debe tener al menos 2 opciones válidas
    }

    $question = new stdClass();
    $question->quizid = $quizid;
    $question->questiontext = trim($q['text']);
    $question->correctanswer = $options[$q['answer']] ?? ''; // Correcta según índice
    $questionid = $DB->insert_record('learningstylesurvey_questions', $question);

    foreach ($options as $opt) {
        $option = new stdClass();
        $option->questionid = $questionid;
        $option->optiontext = trim($opt);
        $DB->insert_record('learningstylesurvey_options', $option);
    }
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
