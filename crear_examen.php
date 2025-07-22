<?php
require_once('../../config.php');
require_login();

$courseid = required_param('courseid', PARAM_INT);

// Obtener cmid del módulo learningstylesurvey
$cm = get_fast_modinfo($courseid)->get_instances_of('learningstylesurvey');
$firstcm = reset($cm);
$cmid = $firstcm->id;

$PAGE->set_url(new moodle_url('/mod/learningstylesurvey/crear_examen.php', array('courseid' => $courseid)));
$PAGE->set_context(context_course::instance($courseid));
$PAGE->set_title('Crear Recurso de Evaluación');
$PAGE->set_heading('Crear Recurso de Evaluación');

echo $OUTPUT->header();
echo $OUTPUT->heading('Formulario para Crear Evaluación');
?>

<form method="post" action="guardar_examen.php">
    <input type="hidden" name="courseid" value="<?php echo $courseid; ?>">

    <div>
        <label>Nombre de la evaluación:</label><br>
        <input type="text" name="quizname" required>
    </div>

    <div id="questions">
        <h4>Pregunta 1:</h4>
        <label>Texto de la pregunta:</label><br>
        <input type="text" name="questions[0][text]" required><br><br>

        <label>Opciones:</label><br>
        <div>
            <input type="radio" name="questions[0][answer]" value="0" required>
            <input type="text" name="questions[0][options][]" required placeholder="Opción 1">
        </div>
        <div>
            <input type="radio" name="questions[0][answer]" value="1">
            <input type="text" name="questions[0][options][]" required placeholder="Opción 2">
        </div>
        <div>
            <input type="radio" name="questions[0][answer]" value="2">
            <input type="text" name="questions[0][options][]" placeholder="Opción 3">
        </div>
        <div>
            <input type="radio" name="questions[0][answer]" value="3">
            <input type="text" name="questions[0][options][]" placeholder="Opción 4">
        </div>
    </div>

    <br>
    <button type="submit">Guardar Evaluación</button>
    <a href="view.php?id=<?php echo $cmid; ?>" class="btn btn-secondary">Regresar al menú</a>
    <button type="button" onclick="agregarPregunta()">Agregar otra pregunta</button>
</form>

<script>
let questionCount = 1;

function agregarPregunta() {
    const container = document.getElementById('questions');

    const div = document.createElement('div');
    div.innerHTML = `
        <hr>
        <h4>Pregunta ${questionCount + 1}:</h4>
        <label>Texto de la pregunta:</label><br>
        <input type="text" name="questions[${questionCount}][text]" required><br><br>

        <label>Opciones:</label><br>
        <div>
            <input type="radio" name="questions[${questionCount}][answer]" value="0" required>
            <input type="text" name="questions[${questionCount}][options][]" required placeholder="Opción 1">
        </div>
        <div>
            <input type="radio" name="questions[${questionCount}][answer]" value="1">
            <input type="text" name="questions[${questionCount}][options][]" required placeholder="Opción 2">
        </div>
        <div>
            <input type="radio" name="questions[${questionCount}][answer]" value="2">
            <input type="text" name="questions[${questionCount}][options][]" placeholder="Opción 3">
        </div>
        <div>
            <input type="radio" name="questions[${questionCount}][answer]" value="3">
            <input type="text" name="questions[${questionCount}][options][]" placeholder="Opción 4">
        </div>
    `;
    container.appendChild(div);
    questionCount++;
}
</script>

<?php
echo $OUTPUT->footer();
?>
