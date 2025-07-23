<?php
require_once('../../config.php');
require_login();

global $DB, $USER;

$courseid = required_param('courseid', PARAM_INT);
$context = context_course::instance($courseid);
$baseurl = new moodle_url('/mod/learningstylesurvey/createsteproute.php', ['courseid' => $courseid]);
$returnurl = new moodle_url('/mod/learningstylesurvey/learningpath.php', ['courseid' => $courseid]);

$PAGE->set_url($baseurl);
$PAGE->set_context($context);
$PAGE->set_title("Ruta de Aprendizaje");
$PAGE->set_heading("Ruta de Aprendizaje");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = required_param('nombre', PARAM_TEXT);
    $archivos = optional_param_array('archivo', [], PARAM_TEXT);
    $evaluaciones = optional_param_array('evaluacion', [], PARAM_INT);

    if (empty($archivos) && empty($evaluaciones)) {
        redirect($baseurl, "Debe seleccionar al menos un recurso o una evaluación.", 3);
    }

    // ✅ Crear registro en learningstylesurvey_paths
    $ruta = new stdClass();
    $ruta->courseid = $courseid;
    $ruta->userid = $USER->id;
    $ruta->name = $nombre;
    $ruta->timecreated = time();
    $pathid = $DB->insert_record('learningstylesurvey_paths', $ruta);

    // ✅ Insertar en learningpath_steps con stepnumber incremental
    $stepnumber = 1;

    // Insertar recursos como pasos
    foreach ($archivos as $file) {
        $resource = $DB->get_record('learningstylesurvey_resources', ['filename' => $file, 'courseid' => $courseid]);
        if ($resource) {
            $step = new stdClass();
            $step->pathid = $pathid;
            $step->stepnumber = $stepnumber++;
            $step->resourceid = $resource->id;
            $step->istest = 0;
            $step->passredirect = 0;
            $step->failredirect = 0;
            $DB->insert_record('learningpath_steps', $step);
        }
    }

    // Insertar evaluaciones como pasos
    foreach ($evaluaciones as $quizid) {
        if (!empty($quizid) && $DB->record_exists('learningstylesurvey_quizzes', ['id' => $quizid])) {
            $step = new stdClass();
            $step->pathid = $pathid;
            $step->stepnumber = $stepnumber++;
            $step->resourceid = $quizid;
            $step->istest = 1;
            $step->passredirect = 0;
            $step->failredirect = 0;
            $DB->insert_record('learningpath_steps', $step);
        }
    }

    redirect($returnurl, "Ruta creada exitosamente.", 2);
}

// ✅ Cargar recursos y evaluaciones disponibles
$resources = $DB->get_records('learningstylesurvey_resources', ['courseid' => $courseid]);
$evaluaciones = $DB->get_records('learningstylesurvey_quizzes', ['courseid' => $courseid]);

echo $OUTPUT->header();
echo $OUTPUT->heading("Crear Ruta de Aprendizaje");
?>

<form method="post">
    <div>
        <label><strong>Nombre de la ruta:</strong></label><br>
        <input type="text" name="nombre" required style="width:100%; max-width:400px;">
    </div>

    <div style="margin-top:15px;">
        <label><strong>Seleccionar recursos:</strong></label><br>
        <select name="archivo[]" id="archivo" style="width:100%; max-width:400px;" onchange="addOption(this,'archivolist')">
            <option value="">-- Seleccione un recurso --</option>
            <?php foreach ($resources as $res): ?>
                <option value="<?php echo $res->filename; ?>">
                    <?php echo format_string($res->name) . " ({$res->style})"; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <div id="archivolist" style="margin-top:10px;"></div>
    </div>

    <div style="margin-top:15px;">
        <label><strong>Seleccionar evaluaciones:</strong></label><br>
        <select name="evaluacion[]" id="evaluacion" style="width:100%; max-width:400px;" onchange="addOption(this,'evaluacionlist')">
            <option value="">-- Seleccione una evaluación --</option>
            <?php foreach ($evaluaciones as $eval): ?>
                <option value="<?php echo $eval->id; ?>">
                    <?php echo format_string($eval->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <div id="evaluacionlist" style="margin-top:10px;"></div>
    </div>

    <div style="margin-top:20px;">
        <button type="submit" class="btn btn-primary">Guardar Ruta</button>
    </div>
</form>

<script>
function addOption(selectElement, containerId) {
    const value = selectElement.value;
    if (!value) return;

    const text = selectElement.options[selectElement.selectedIndex].text;
    const container = document.getElementById(containerId);

    if (container.querySelector("input[value='" + value + "']")) return;

    const input = document.createElement("input");
    input.type = "hidden";
    input.name = selectElement.name;
    input.value = value;

    const label = document.createElement("div");
    label.textContent = text;
    label.style.marginTop = "5px";
    label.appendChild(input);

    container.appendChild(label);
    selectElement.selectedIndex = 0;
}
</script>

<?php
$urlreturn = new moodle_url('/mod/learningstylesurvey/learningpath.php', ['courseid' => $courseid]);
echo "<br><a href='{$urlreturn}' class='btn btn-secondary'>Regresar al menú anterior</a>";
echo $OUTPUT->footer();
?>
