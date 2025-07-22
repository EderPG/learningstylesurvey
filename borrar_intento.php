
<?php
require_once('../../config.php');
require_login();
require_capability('moodle/course:manageactivities', context_system::instance());

$quizid = required_param('quizid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);

// Borrar intento del estudiante
$DB->delete_records('learningstylesurvey_quiz_results', [
    'quizid' => $quizid,
    'userid' => $userid,
    'courseid' => $courseid
]);

redirect(new moodle_url('/mod/learningstylesurvey/vista_estudiante.php', ['courseid' => $courseid]), 'Intento eliminado correctamente');
?>
