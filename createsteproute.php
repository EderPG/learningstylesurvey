<?php
require_once('../../config.php');
require_login();

global $DB, $USER;

$courseid = required_param('courseid', PARAM_INT);
$context = context_course::instance($courseid);
$baseurl = new moodle_url('/mod/learningstylesurvey/createsteproute.php', array('courseid' => $courseid));
$returnurl = new moodle_url('/mod/learningstylesurvey/learningpath.php', array('courseid' => $courseid));

$PAGE->set_url($baseurl);
$PAGE->set_context($context);
$PAGE->set_title("Ruta de Aprendizaje");
$PAGE->set_heading("Ruta de Aprendizaje");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = required_param('nombre', PARAM_TEXT);
    $archivos = optional_param_array('archivo', [], PARAM_TEXT);
    $evaluaciones = optional_param_array('evaluacion', [], PARAM_INT);

    if (empty($archivos) && empty($evaluaciones)) {
        redirect($baseurl, "Debe seleccionar al menos un archivo o una evaluación.", 3);
    }

    $data = new stdClass();
    $data->courseid = $courseid;
    $data->userid = $USER->id;
    $data->name = $nombre;
    $data->filename = '';
    $data->timecreated = time();

    $pathid = $DB->insert_record('learningstylesurvey_paths', $data);

    foreach ($archivos as $file) {
        // Obtener ID desde inforoute
        $inforoute = $DB->get_record('learningstylesurvey_inforoute', ['filename' => $file, 'courseid' => $courseid]);
        if ($inforoute) {
            $f = new stdClass();
            $f->pathid = $pathid;
            $f->fileid = $inforoute->id;
            $f->steporder = 0; // Si usas orden personalizado lo puedes ajustar
            $DB->insert_record('learningstylesurvey_path_files', $f);
        }
    }

    foreach ($evaluaciones as $quizid) {
        $e = new stdClass();
        $e->pathid = $pathid;
        $e->quizid = $quizid;
        $e->steporder = 0;
        $DB->insert_record('learningstylesurvey_path_evaluations', $e);
    }

    redirect($returnurl, "Ruta guardada correctamente.", 2);
}

$uploadspath = __DIR__ . '/uploads';
$archivos = is_dir($uploadspath) ? array_diff(scandir($uploadspath), ['.', '..']) : [];
$estilosPorArchivo = $DB->get_records_menu('learningstylesurvey_resources', null, '', 'filename, style');
$evaluaciones = $DB->get_records('learningstylesurvey_quizzes', ['courseid' => $courseid]);

echo $OUTPUT->header();
echo $OUTPUT->heading("Crear Ruta de Aprendizaje");
?>

<form method="post">
    <div>
        <label>Nombre de la ruta:</label><br>
        <input type="text" name="nombre" required>
    </div>

    <div style="margin-top: 10px;">
        <label for="archivo">Cargar recurso:</label><br>
        <select name="archivo[]" id="archivo" style="width: 100%; max-width: 400px;" onchange="addOption(this, 'archivolist')">
            <option value="">Seleccione un recurso</option>
            <?php foreach ($archivos as $file): ?>
                <?php $estilo = isset($estilosPorArchivo[$file]) ? $estilosPorArchivo[$file] : 'Sin estilo'; ?>
                <option value="<?php echo $file; ?>"><?php echo $file . " ($estilo)"; ?></option>
            <?php endforeach; ?>
        </select>
        <div id="archivolist"></div>
    </div>

    <div style="margin-top: 10px;">
        <label for="evaluacion">Cargar evaluaciones:</label><br>
        <select name="evaluacion[]" id="evaluacion" style="width: 100%; max-width: 400px;" onchange="addOption(this, 'evaluacionlist')">
            <option value="">Seleccione una evaluación</option>
            <?php foreach ($evaluaciones as $eval): ?>
                <option value="<?php echo $eval->id; ?>"><?php echo format_string($eval->name); ?></option>
            <?php endforeach; ?>
        </select>
        <div id="evaluacionlist"></div>
    </div>

    <div style="margin-top: 15px;">
        <button type="submit">Guardar ruta</button>
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
