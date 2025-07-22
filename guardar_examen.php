<?php
require_once('../../config.php');
require_login();

global $DB;

$courseid = required_param('courseid', PARAM_INT);
$quizname = required_param('quizname', PARAM_TEXT);
$questions = $_POST['questions'] ?? [];

// Insert quiz
$quiz = new stdClass();
$quiz->userid = $USER->id;
$quiz->timecreated = time();
$quiz->courseid = $courseid;
$quiz->name = $quizname;
$DB->insert_record('learningstylesurvey_quizzes', $quiz);

// Get inserted quiz ID
$quizid = $DB->get_field('learningstylesurvey_quizzes', 'id', ['name' => $quizname, 'courseid' => $courseid], MUST_EXIST);

// Insert questions
foreach ($questions as $q) {
    if (!isset($q['text']) || !isset($q['options']) || !isset($q['answer'])) continue;

    $question = new stdClass();
    $question->quizid = $quizid;
    $question->questiontext = $q['text'];
    $question->correctanswer = $q['answer'];
    $questionid = $DB->insert_record('learningstylesurvey_questions', $question);

    foreach ($q['options'] as $opt) {
        if (trim($opt) === '') continue;
        $option = new stdClass();
        $option->questionid = $questionid;
        $option->optiontext = $opt;
        $DB->insert_record('learningstylesurvey_options', $option);
    }
}

redirect(new moodle_url('/course/view.php', array('id' => $courseid)), 'Evaluación guardada con éxito.', 2);
?>
