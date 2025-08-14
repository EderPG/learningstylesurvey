<?php
require_once("../../config.php");
require_login();

$id = required_param('id', PARAM_INT); // Course module ID
$cm = get_coursemodule_from_id('learningstylesurvey', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$survey = $DB->get_record('learningstylesurvey', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);

$PAGE->set_url('/mod/learningstylesurvey/results.php', ['id' => $id]);
$PAGE->set_context($context);
$PAGE->set_cm($cm);
$PAGE->set_title(format_string($survey->name));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo $OUTPUT->heading("Resultados del test de estilos de aprendizaje");

// Obtener lista de usuarios que han respondido esta encuesta
$sqlusers = "SELECT DISTINCT u.id, u.firstname, u.lastname
             FROM {user} u
             JOIN {learningstylesurvey_responses} r ON r.userid = u.id
             WHERE r.surveyid = :surveyid
             ORDER BY u.lastname, u.firstname";
$respondents = $DB->get_records_sql($sqlusers, ['surveyid' => $survey->id]);

// Obtener userid para mostrar resultados (por GET), por defecto el usuario actual
$selecteduserid = optional_param('userid', $USER->id, PARAM_INT);

// Verificar si el usuario seleccionado realmente respondió la encuesta, o es 'general' (0)
if ($selecteduserid != 0 && !array_key_exists($selecteduserid, $respondents)) {
    echo $OUTPUT->notification('El usuario seleccionado no ha respondido la encuesta.', 'notifywarning');
    $selecteduserid = $USER->id;
}

// Mostrar formulario para seleccionar usuario o general
echo html_writer::start_tag('form', ['method' => 'get', 'action' => $PAGE->url->out(false), 'style' => 'margin-bottom:20px;']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $id]);

$options = ['0' => 'General (Todos los usuarios)'];
foreach ($respondents as $user) {
    $fullname = fullname($user);
    $options[$user->id] = $fullname;
}

echo html_writer::select($options, 'userid', $selecteduserid, false, ['onchange' => 'this.form.submit();']);
echo html_writer::end_tag('form');

// Mapeo de preguntas a estilos
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

if ($selecteduserid == 0) {
    // General: tomar solo el último intento de cada usuario
    $responses = [];
    foreach ($respondents as $user) {
        $userresponses = $DB->get_records('learningstylesurvey_responses', ['userid' => $user->id, 'surveyid' => $survey->id], 'timecreated DESC', '*', 0, 44);
        $responses = array_merge($responses, $userresponses);
    }
    if (!$responses) {
        echo $OUTPUT->notification("No hay respuestas registradas para esta encuesta.", 'notifymessage');
        echo $OUTPUT->footer();
        exit;
    }
    $title = "Resultados generales";
} else {
    // Individual: últimas 44 respuestas del usuario
    $responses = $DB->get_records('learningstylesurvey_responses', ['userid' => $selecteduserid, 'surveyid' => $survey->id], 'timecreated DESC', '*', 0, 44);
    if (!$responses) {
        echo $OUTPUT->notification("El usuario seleccionado no ha respondido la encuesta.", 'notifymessage');
        echo $OUTPUT->footer();
        exit;
    }
    $title = "Resultados individuales: " . fullname($respondents[$selecteduserid]);
}

// Contar respuestas por estilo
$stylecounts = [
    'Activo' => 0, 'Reflexivo' => 0,
    'Sensorial' => 0, 'Intuitivo' => 0,
    'Visual' => 0, 'Verbal' => 0,
    'Secuencial' => 0, 'Global' => 0
];

foreach ($responses as $r) {
    $qid = $r->questionid;
    $answer = intval($r->response);
    if (isset($stylemap[$qid])) {
        $style = $stylemap[$qid][$answer];
        $stylecounts[$style]++;
    }
}

arsort($stylecounts);
$strongest = array_key_first($stylecounts);

echo html_writer::tag('h3', $title);
echo html_writer::tag('p', "<strong>Estilo más fuerte: </strong> $strongest");

// Mostrar conteo por estilo
echo html_writer::start_tag('ul');
foreach ($stylecounts as $estilo => $cantidad) {
    echo html_writer::tag('li', "$estilo: $cantidad respuestas");
}
echo html_writer::end_tag('ul');

// Gráfica
$labels = json_encode(array_keys($stylecounts));
$data = json_encode(array_values($stylecounts));
$colors = json_encode([
    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
    '#9966FF', '#FF9F40', '#C9CBCF', '#8AFFC1'
]);
?>
<div style="max-width: 600px; margin: 20px auto;">
    <canvas id="resultChart"></canvas>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('resultChart').getContext('2d');
new Chart(ctx, {
    type: 'pie',
    data: {
        labels: <?php echo $labels; ?>,
        datasets: [{
            data: <?php echo $data; ?>,
            backgroundColor: <?php echo $colors; ?>,
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'right' },
            title: { display: false }
        }
    }
});
</script>

<br>
<form action="view.php" method="get">
    <input type="hidden" name="id" value="<?php echo $cm->id; ?>">
    <button type="submit">Volver al menu</button>
</form>

<?php
echo $OUTPUT->footer();
