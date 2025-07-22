<?php
require_once('../../config.php');
require_login();

global $DB, $OUTPUT, $PAGE, $USER;

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('learningstylesurvey', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
$courseid = $course->id;
$courseid = optional_param('courseid', 0, PARAM_INT);



$PAGE->set_context($context);

// Mostrar botón solo si es profesor o administrador
if (has_capability('mod/learningstylesurvey:view', $context) || has_capability('mod/learningstylesurvey:addinstance', $context) || has_capability('moodle/course:update', $context)) {
    
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    for ($i = 1; $i <= 44; $i++) {
        $key = 'ilsq' . $i;
        if (isset($_POST[$key])) {
            $record = new stdClass();
            $record->userid = $USER->id;
            $record->questionid = $i;
            $record->response = intval($_POST[$key]);
            $record->timecreated = time();
            
    global $DB, $USER;

    $courseid = $course->id;
    $userid = $USER->id;
    $now = time();

    foreach ($_POST as $key => $value) {
        if (strpos($key, 'q') === 0) {
            $questionid = intval(substr($key, 1));
            $response = clean_param($value, PARAM_TEXT);

            $record = new stdClass();
            $record->courseid = $courseid;
            $record->userid = $userid;
            $record->questionid = $questionid;
            $record->response = $response;
            $record->timemodified = $now;

            $DB->insert_record('learningstylesurvey_responses', $record);
        }
    }

        }
    }
    
    // Calcular conteo de respuestas por estilo
    $stylecounts = [
        'Activo' => 0, 'Reflexivo' => 0,
        'Sensorial' => 0, 'Intuitivo' => 0,
        'Visual' => 0, 'Verbal' => 0,
        'Secuencial' => 0, 'Global' => 0
    ];
    $stylemap = [
        1 => ['Activo','Reflexivo'], 2 => ['Sensorial','Intuitivo'], 3 => ['Visual','Verbal'], 4 => ['Secuencial','Global'],
        5 => ['Activo','Reflexivo'], 6 => ['Sensorial','Intuitivo'], 7 => ['Visual','Verbal'], 8 => ['Secuencial','Global'],
        9 => ['Activo','Reflexivo'],10 => ['Sensorial','Intuitivo'],11 => ['Visual','Verbal'],12 => ['Secuencial','Global'],
        13=> ['Activo','Reflexivo'],14 => ['Sensorial','Intuitivo'],15 => ['Visual','Verbal'],16 => ['Secuencial','Global'],
        17=> ['Activo','Reflexivo'],18 => ['Sensorial','Intuitivo'],19 => ['Visual','Verbal'],20 => ['Secuencial','Global'],
        21=> ['Activo','Reflexivo'],22 => ['Sensorial','Intuitivo'],23 => ['Visual','Verbal'],24 => ['Secuencial','Global'],
        25=> ['Activo','Reflexivo'],26 => ['Sensorial','Intuitivo'],27 => ['Visual','Verbal'],28 => ['Secuencial','Global'],
        29=> ['Activo','Reflexivo'],30 => ['Sensorial','Intuitivo'],31 => ['Visual','Verbal'],32 => ['Secuencial','Global'],
        33=> ['Activo','Reflexivo'],34 => ['Sensorial','Intuitivo'],35 => ['Visual','Verbal'],36 => ['Secuencial','Global'],
        37=> ['Activo','Reflexivo'],38 => ['Sensorial','Intuitivo'],39 => ['Visual','Verbal'],40 => ['Secuencial','Global'],
        41=> ['Activo','Reflexivo'],42 => ['Sensorial','Intuitivo'],43 => ['Visual','Verbal'],44 => ['Secuencial','Global']
    ];
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'ilsq') === 0) {
            $qid = intval(substr($key, 4));
            $answer = intval($value);
            if (isset($stylemap[$qid])) {
                $stylecounts[$stylemap[$qid][$answer]]++;
            }
        }
    }
    arsort($stylecounts);
    $strongest = array_key_first($stylecounts);

    $DB->delete_records('learningstylesurvey_results', array('userid' => $USER->id));

    $record = new stdClass();
    $record->userid = $USER->id;
    $record->strongeststyle = $strongest;
    $DB->insert_record('learningstylesurvey_results', $record);

    redirect(new moodle_url('/course/view.php', array('id' => $courseid)));
    exit;
}

echo html_writer::tag('h2', get_string('pluginname', 'learningstylesurvey'));
echo '<form method="post">';
for ($i = 1; $i <= 44; $i++) {
    $qkey = "ilsq{$i}";
    $a0key = "ilsq{$i}a0";
    $a1key = "ilsq{$i}a1";
    echo '<div style="margin-bottom: 20px;">';
    echo '<label><strong>' . get_string($qkey, 'learningstylesurvey') . '</strong></label><br>';
    echo "<label><input type='radio' name='{$qkey}' value='0' required> " . get_string($a0key, 'learningstylesurvey') . '</label><br>';
    echo "<label><input type='radio' name='{$qkey}' value='1'> " . get_string($a1key, 'learningstylesurvey') . '</label>';
    echo '</div>';
}
echo '<input type="hidden" name="courseid" value="' . $courseid . '">';
echo '<input type="submit" value="Enviar respuestas">';
echo '</form>';
echo html_writer::div(
    html_writer::link(new moodle_url('/mod/learningstylesurvey/view.php', array('id' => 187)), 'Regresar al curso', array('class' => 'btn btn-dark', 'style' => 'margin-top: 30px;')),
    'regresar-curso'
);
?>
