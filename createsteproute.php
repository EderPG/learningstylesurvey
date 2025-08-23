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
    $temas_hidden = optional_param('temas_hidden', '', PARAM_RAW);
    $temas_ids = [];
    if (!empty($temas_hidden)) {
        $temas_ids = array_filter(explode(',', $temas_hidden));
    }
    $evaluaciones = optional_param('evaluacion_hidden', '', PARAM_RAW);

    $archivos = [];
    foreach ($temas_ids as $tema_id) {
        $archivos_tema = $DB->get_records('learningstylesurvey_resources', [
            'courseid' => $courseid,
            'tema' => $tema_id
        ]);
        $archivos = array_merge($archivos, $archivos_tema);
    }

    // Convertir evaluaciones seleccionadas (del campo oculto) a array
    $evaluaciones_array = [];
    if (!empty($evaluaciones)) {
        $evaluaciones_array = array_filter(explode(',', $evaluaciones));
    }

    if (empty($archivos) && empty($evaluaciones_array)) {
        redirect($baseurl, "Debe existir al menos un recurso o una evaluación para los temas seleccionados.", 3);
    }

    // ✅ Crear registro en learningstylesurvey_paths
    $ruta = new stdClass();
    $ruta->courseid = $courseid;
    $ruta->userid = $USER->id;
    $ruta->name = $nombre;
    $ruta->timecreated = time();
    $pathid = $DB->insert_record('learningstylesurvey_paths', $ruta);

    // ✅ Guardar los temas seleccionados en la tabla de asociación
    foreach ($temas_ids as $orden => $tema_id) {
        $record = new stdClass();
        $record->pathid = $pathid;
        $record->temaid = $tema_id;
        $record->orden = $orden + 1;
        $record->isrefuerzo = 0;
        $DB->insert_record('learningstylesurvey_path_temas', $record);
    }

    // ✅ Insertar archivos en learningstylesurvey_path_files
    foreach ($archivos as $resource) {
        $rec = new stdClass();
        $rec->pathid = $pathid;
        $rec->filename = $resource->filename;
        $rec->steporder = 0;
        $DB->insert_record('learningstylesurvey_path_files', $rec);
    }

    // ✅ Insertar evaluaciones en learningstylesurvey_path_evaluations
    foreach ($evaluaciones_array as $quizid) {
        if ($DB->record_exists('learningstylesurvey_quizzes', ['id' => $quizid])) {
            $rec = new stdClass();
            $rec->pathid = $pathid;
            $rec->quizid = $quizid;
            $DB->insert_record('learningstylesurvey_path_evaluations', $rec);
        }
    }

    // ✅ Insertar pasos
    $stepnumber = 1;

    // Insertar recursos automáticamente
    foreach ($archivos as $resource) {
        $step = new stdClass();
        $step->pathid = $pathid;
        $step->stepnumber = $stepnumber++;
        $step->resourceid = $resource->id;
        $step->istest = 0;
        $step->passredirect = 0;
        $step->failredirect = 0;
        $DB->insert_record('learningpath_steps', $step);
    }

    // Insertar evaluaciones como pasos
    foreach ($evaluaciones_array as $quizid) {
        if ($DB->record_exists('learningstylesurvey_quizzes', ['id' => $quizid])) {
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

// ✅ Cargar temas y evaluaciones
$temas = $DB->get_records('learningstylesurvey_temas', ['courseid' => $courseid]);
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
        <label><strong>Seleccionar temas:</strong></label><br>
        <select id="tema_select" style="width:100%; max-width:400px;">
            <option value="">-- Seleccione un tema --</option>
            <?php foreach ($temas as $tema): ?>
                <option value="<?php echo $tema->id; ?>">
                    <?php echo format_string($tema->tema); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="button" class="btn btn-info" onclick="agregarTema()">Agregar</button>
        <ul id="temas_lista" style="margin-top:10px;"></ul>
        <input type="hidden" name="temas_hidden" id="temas_hidden">
        <small style="color:gray;">Todos los archivos asociados a los temas seleccionados se incluirán automáticamente.</small>
    </div>

    <div style="margin-top:15px;">
        <label><strong>Seleccionar evaluaciones:</strong></label><br>
        <select id="evaluacion_select" style="width:100%; max-width:400px;">
            <option value="">-- Seleccione una evaluación --</option>
            <?php foreach ($evaluaciones as $eval): ?>
                <option value="<?php echo $eval->id; ?>">
                    <?php echo format_string($eval->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="button" class="btn btn-info" onclick="agregarEvaluacion()">Agregar</button>
        <ul id="evaluaciones_lista" style="margin-top:10px;"></ul>
        <input type="hidden" name="evaluacion_hidden" id="evaluacion_hidden">
    </div>

    <div style="margin-top:20px;">
        <button type="submit" class="btn btn-primary">Guardar Ruta</button>
    </div>
</form>

<script>
let temasSeleccionados = [];
let evaluacionesSeleccionadas = [];

function agregarTema() {
    const select = document.getElementById('tema_select');
    const temaId = select.value;
    const temaText = select.options[select.selectedIndex].text;

    if (temaId && !temasSeleccionados.includes(temaId)) {
        temasSeleccionados.push(temaId);

        const ul = document.getElementById('temas_lista');
        const li = document.createElement('li');
        li.textContent = temaText;
        li.setAttribute('data-id', temaId);

        const btn = document.createElement('button');
        btn.textContent = 'Quitar';
        btn.className = 'btn btn-sm btn-danger ml-2';
        btn.onclick = function() {
            ul.removeChild(li);
            temasSeleccionados = temasSeleccionados.filter(id => id !== temaId);
            actualizarCampoTemas();
        };
        li.appendChild(btn);

        ul.appendChild(li);
        actualizarCampoTemas();
    }
}

function actualizarCampoTemas() {
    document.getElementById('temas_hidden').value = temasSeleccionados.join(',');
}

function agregarEvaluacion() {
    const select = document.getElementById('evaluacion_select');
    const evalId = select.value;
    const evalText = select.options[select.selectedIndex].text;

    if (evalId && !evaluacionesSeleccionadas.includes(evalId)) {
        evaluacionesSeleccionadas.push(evalId);

        const ul = document.getElementById('evaluaciones_lista');
        const li = document.createElement('li');
        li.textContent = evalText;
        li.setAttribute('data-id', evalId);

        const btn = document.createElement('button');
        btn.textContent = 'Quitar';
        btn.className = 'btn btn-sm btn-danger ml-2';
        btn.onclick = function() {
            ul.removeChild(li);
            evaluacionesSeleccionadas = evaluacionesSeleccionadas.filter(id => id !== evalId);
            actualizarCampoEvaluaciones();
        };
        li.appendChild(btn);

        ul.appendChild(li);
        actualizarCampoEvaluaciones();
    }
}

function actualizarCampoEvaluaciones() {
    document.getElementById('evaluacion_hidden').value = evaluacionesSeleccionadas.join(',');
}
</script>

<?php
$urlreturn = new moodle_url('/mod/learningstylesurvey/learningpath.php', ['courseid' => $courseid]);
echo "<br><a href='{$urlreturn}' class='btn btn-secondary'>Regresar al menú anterior</a>";
echo $OUTPUT->footer();
?>
