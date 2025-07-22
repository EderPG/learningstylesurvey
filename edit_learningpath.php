<?php
require_once('../../config.php');
require_login();

$courseid = required_param('courseid', PARAM_INT);
$context = context_course::instance($courseid);

$PAGE->set_url(new moodle_url('/mod/learningstylesurvey/edit_learningpath.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_title("Editar Ruta de Aprendizaje");
$PAGE->set_heading("Editar Ruta de Aprendizaje");

global $DB;

// Obtener rutas del curso
$rutas = $DB->get_records('learningstylesurvey_paths', ['courseid' => $courseid]);

if (!isset($_POST['pathid'])) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading("Seleccionar Ruta de Aprendizaje");

    echo '<form method="post">';
    echo '<input type="hidden" name="courseid" value="' . $courseid . '">';
    echo '<label>Ruta a editar: </label>';
    echo '<select name="pathid" required>';
    echo '<option value="">-- Selecciona una ruta --</option>';
    foreach ($rutas as $ruta) {
        echo '<option value="' . $ruta->id . '">' . format_string($ruta->name) . '</option>';
    }
    echo '</select> ';
    echo '<button type="submit">Editar</button>';
    echo '</form>';

    echo $OUTPUT->footer();
    exit;
}

$pathid = (int) $_POST['pathid'];
$ruta = $DB->get_record('learningstylesurvey_paths', ['id' => $pathid, 'courseid' => $courseid], '*', MUST_EXIST);

// Archivos
$archivosActuales = $DB->get_records('learningstylesurvey_path_files', ['pathid' => $pathid]);
$archivosUsados = array_map(fn($a) => $a->filename, $archivosActuales);

$uploadspath = __DIR__ . '/uploads';
$archivosDisponibles = is_dir($uploadspath) ? array_diff(scandir($uploadspath), ['.', '..']) : [];
$archivosDisponibles = array_filter($archivosDisponibles, fn($a) => trim($a) !== '');

$estilos = $DB->get_records_menu('learningstylesurvey_resources', null, '', 'filename,style');

// Evaluaciones
$evaluaciones = $DB->get_records('learningstylesurvey_quizzes', ['courseid' => $courseid]);
$evaluacionesActuales = $DB->get_records('learningstylesurvey_path_evaluations', ['pathid' => $pathid]);
$evaluacionesUsadas = array_map(fn($e) => $e->quizid, $evaluacionesActuales);

// Guardar cambios
if (isset($_POST['guardar']) && confirm_sesskey()) {
    $nombre = required_param('nombre', PARAM_TEXT);
    $archivosSeleccionados = optional_param_array('archivos', [], PARAM_TEXT);
    $evaluacionesSeleccionadas = optional_param_array('evaluaciones', [], PARAM_INT);

    $DB->delete_records('learningstylesurvey_path_files', ['pathid' => $pathid]);
    $DB->delete_records('learningstylesurvey_path_evaluations', ['pathid' => $pathid]);

    foreach ($archivosSeleccionados as $archivo) {
        if (trim($archivo) === '') continue;
        $rec = new stdClass();
        $rec->pathid = $pathid;
        $rec->filename = $archivo;
        $DB->insert_record('learningstylesurvey_path_files', $rec);
    }

    foreach ($evaluacionesSeleccionadas as $eval) {
        if ($eval == 0) continue;
        $rec = new stdClass();
        $rec->pathid = $pathid;
        $rec->quizid = $eval;
        $DB->insert_record('learningstylesurvey_path_evaluations', $rec);
    }

    $ruta->name = $nombre;
    $DB->update_record('learningstylesurvey_paths', $ruta);

    redirect(new moodle_url('/mod/learningstylesurvey/learningpath.php', ['courseid' => $courseid]), "Ruta actualizada", 2);
}

// Mostrar formulario
echo $OUTPUT->header();
echo $OUTPUT->heading("Editar Ruta de Aprendizaje");
?>

<form method="post">
    <?php echo '<input type="hidden" name="sesskey" value="' . sesskey() . '">'; ?>
    <input type="hidden" name="courseid" value="<?php echo $courseid; ?>">
    <input type="hidden" name="pathid" value="<?php echo $pathid; ?>">

    <div>
        <label>Nombre:</label><br>
        <input type="text" name="nombre" value="<?php echo s($ruta->name); ?>" required>
    </div>

    <br>
    <div>
        <label>Recursos Educativos en la ruta actualmente:</label><br>
        <?php foreach ($archivosActuales as $a): 
            if (trim($a->filename) === '') continue;
            $estilo = $estilos[$a->filename] ?? 'Sin estilo';
        ?>
            <label style="margin:4px; display:inline-block; padding:6px 10px; background:#dff0d8; border-radius:6px;">
                <input type="checkbox" name="archivos[]" value="<?php echo $a->filename; ?>" checked>
                <?php echo $a->filename . " ({$estilo}) (actual)"; ?>
            </label>
        <?php endforeach; ?>
    </div>

    <div style="margin-top: 10px;">
        <button type="button" onclick="document.getElementById('archivoextra').style.display='block'">Agregar más archivos</button><br>
        <div id="archivoextra" style="margin-top:8px; display:none;">
            <select name="archivos[]">
                <option value="">Seleccione un archivo</option>
                <?php foreach ($archivosDisponibles as $file): 
                    if (in_array($file, $archivosUsados)) continue;
                    $estilo = $estilos[$file] ?? 'Sin estilo';
                ?>
                    <option value="<?php echo $file; ?>"><?php echo $file . " ({$estilo})"; ?> (no usado)</option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <br>
    <div>
        <label>Evaluaciones en la ruta actualmente:</label><br>
        <?php foreach ($evaluaciones as $eval): ?>
            <?php if (in_array($eval->id, $evaluacionesUsadas)): ?>
                <label style="margin:4px; display:inline-block; padding:6px 10px; background:#d9edf7; border-radius:6px;">
                    <input type="checkbox" name="evaluaciones[]" value="<?php echo $eval->id; ?>" checked>
                    <?php echo format_string($eval->name); ?> (actual)
                </label>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div style="margin-top: 10px;">
        <button type="button" onclick="document.getElementById('evalextra').style.display='block'">Agregar más evaluaciones</button><br>
        <div id="evalextra" style="margin-top:8px; display:none;">
            <select name="evaluaciones[]">
                <option value="0">Seleccione una evaluación</option>
                <?php foreach ($evaluaciones as $eval): ?>
                    <?php if (!in_array($eval->id, $evaluacionesUsadas)): ?>
                        <option value="<?php echo $eval->id; ?>"><?php echo format_string($eval->name); ?> (no usada)</option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <br><br>
    <button type="submit" name="guardar">Guardar cambios</button>
    <a href="learningpath.php?courseid=<?php echo $courseid; ?>" class="btn btn-secondary">Regresar Menu Anterior</a>
</form>

<?php echo $OUTPUT->footer(); ?>
