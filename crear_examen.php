<?php
require_once('../../config.php');
require_login();

$courseid = required_param('courseid', PARAM_INT);
$cmid = optional_param('cmid', 0, PARAM_INT); // ID de la instancia específica

// Usar el cmid correcto si se proporcionó, sino buscar la primera instancia
if ($cmid > 0) {
    $targetcmid = $cmid;
} else {
    // Intentar obtener el cmid del módulo learningstylesurvey
    $targetcmid = 0;
    $instances = get_fast_modinfo($courseid)->get_instances_of('learningstylesurvey');
    if ($instances) {
        $firstcm = reset($instances);
        $targetcmid = $firstcm->id;
    }
}

$PAGE->set_url(new moodle_url('/mod/learningstylesurvey/crear_examen.php', ['courseid' => $courseid, 'cmid' => $cmid]));
$PAGE->set_context(context_course::instance($courseid));
$PAGE->set_title('Crear Recurso de Evaluación');
$PAGE->set_heading('Crear Recurso de Evaluación');

echo $OUTPUT->header();
echo $OUTPUT->heading('Formulario para Crear Evaluación');
?>

<form method="post" action="guardar_examen.php">
    <input type="hidden" name="courseid" value="<?php echo $courseid; ?>">

    <div>
        <label><strong>Nombre de la evaluación:</strong></label><br>
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
    <button type="submit" class="btn btn-primary">Guardar Evaluación</button>

    <!-- ✅ Botón regresar -->
    <?php if ($targetcmid): ?>
        <?php 
        $viewurl = new moodle_url('/mod/learningstylesurvey/view.php', ['id' => $targetcmid]);
        echo '<a href="' . $viewurl->out() . '" class="btn btn-secondary">Regresar al menú</a>';
        ?>
    <?php else: ?>
        <a href="<?php echo new moodle_url('/course/view.php', ['id' => $courseid]); ?>" class="btn btn-secondary">Regresar al curso</a>
    <?php endif; ?>

    <button type="button" onclick="agregarPregunta()" class="btn btn-info">Agregar otra pregunta</button>
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
