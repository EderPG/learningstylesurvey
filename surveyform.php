<?php
require_once('../../config.php');
require_login();

global $DB, $OUTPUT, $PAGE, $USER;

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('learningstylesurvey', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
$courseid = $course->id;

$PAGE->set_context($context);
$PAGE->set_cm($cm);
$PAGE->set_url('/mod/learningstylesurvey/surveyform.php', ['id' => $id]);
$PAGE->set_title(get_string('pluginname', 'learningstylesurvey'));
$PAGE->set_heading(format_string($course->fullname));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $now = time();

    // Borrar respuestas anteriores solo del usuario actual
    $DB->delete_records('learningstylesurvey_responses', [
        'userid' => $USER->id, 
        'surveyid' => $cm->instance
    ]);

    // Guardar cada respuesta individual
    for ($i = 1; $i <= 44; $i++) {
        $key = 'ilsq' . $i;
        if (isset($_POST[$key])) {
            $response = intval($_POST[$key]);

            $record = new stdClass();
            $record->courseid = $courseid;
            $record->userid = $USER->id;
            $record->questionid = $i;
            $record->response = $response;
            $record->surveyid = $cm->instance;
            $record->timecreated = $now; // timestamp

            $DB->insert_record('learningstylesurvey_responses', $record);
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
        21=> ['Activo','Reflexivo'],22=> ['Sensorial','Intuitivo'],23=> ['Visual','Verbal'],24=> ['Secuencial','Global'],
        25=> ['Activo','Reflexivo'],26=> ['Sensorial','Intuitivo'],27=> ['Visual','Verbal'],28=> ['Secuencial','Global'],
        29=> ['Activo','Reflexivo'],30=> ['Sensorial','Intuitivo'],31=> ['Visual','Verbal'],32=> ['Secuencial','Global'],
        33=> ['Activo','Reflexivo'],34=> ['Sensorial','Intuitivo'],35=> ['Visual','Verbal'],36=> ['Secuencial','Global'],
        37=> ['Activo','Reflexivo'],38=> ['Sensorial','Intuitivo'],39=> ['Visual','Verbal'],40=> ['Secuencial','Global'],
        41=> ['Activo','Reflexivo'],42=> ['Sensorial','Intuitivo'],43=> ['Visual','Verbal'],44=> ['Secuencial','Global']
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

    // Borrar resultados anteriores solo del usuario actual
    $DB->delete_records('learningstylesurvey_results', ['userid' => $USER->id]);
    $DB->delete_records('learningstylesurvey_userstyles', ['userid' => $USER->id]);

    // Insertar nuevo resultado
    $result = new stdClass();
    $result->userid = $USER->id;
    $result->strongeststyle = $strongest;
    $result->timecreated = $now;
    $DB->insert_record('learningstylesurvey_results', $result);

    // Guardar estilo del usuario para filtrado futuro
    $userstyle = new stdClass();
    $userstyle->userid = $USER->id;
    $userstyle->style = $strongest;
    $userstyle->timemodified = $now;
    $DB->insert_record('learningstylesurvey_userstyles', $userstyle);

    redirect(new moodle_url('/mod/learningstylesurvey/view.php', ['id' => $id]));
    exit;
}

echo $OUTPUT->header();
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
    html_writer::link(new moodle_url('/mod/learningstylesurvey/view.php', ['id' => $id]), 'Regresar al menu', ['class' => 'btn btn-dark', 'style' => 'margin-top: 30px;']),
    'regresar-curso'
);

echo $OUTPUT->footer();
