
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

// Obtener respuestas del usuario
$responses = $DB->get_records('learningstylesurvey_responses', ['userid' => $USER->id, 'surveyid' => $survey->id]);

if (!$responses) {
    echo $OUTPUT->notification("Aún no has respondido la encuesta.", 'notifymessage');
    echo $OUTPUT->footer();
    exit;
}

// Contar ocurrencias por estilo
$conteo = [];
foreach ($responses as $r) {
    $estilo = strtolower(trim($r->response));
    if (!isset($conteo[$estilo])) {
        $conteo[$estilo] = 0;
    }
    $conteo[$estilo]++;
}

// Mostrar resultados
echo html_writer::start_tag('ul');
foreach ($conteo as $estilo => $cantidad) {
    echo html_writer::tag('li', ucfirst($estilo) . ": $cantidad respuestas");
}
echo html_writer::end_tag('ul');

// Preparar datos para gráfica
$labels = json_encode(array_keys($conteo));
$data = json_encode(array_values($conteo));
?>

<canvas id="resultChart" width="400" height="200"></canvas>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('resultChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo $labels; ?>,
        datasets: [{
            label: 'Respuestas por estilo',
            data: <?php echo $data; ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.6)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>

<br>
<form action="../../course/view.php" method="get">
    <input type="hidden" name="id" value="<?php echo $course->id; ?>">
    <button type="submit">Volver al curso</button>
</form>

<?php

echo $OUTPUT->footer();
