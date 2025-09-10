<?php
require_once('../../../config.php');
require_login();

$courseid = required_param('courseid', PARAM_INT);
$cmid = optional_param('cmid', 0, PARAM_INT); // Para aislamiento por instancia
$context = context_course::instance($courseid);
$PAGE->set_url(new moodle_url('/mod/learningstylesurvey/path/delete_learningpath.php', array('courseid' => $courseid, 'cmid' => $cmid)));
$PAGE->set_context($context);
$PAGE->set_title("Eliminar Ruta de Aprendizaje");
$PAGE->set_heading("Eliminar Ruta de Aprendizaje");

echo $OUTPUT->header();
global $DB;

// Si se envió confirmación de eliminación
if (optional_param('confirm', 0, PARAM_BOOL)) {
    $idruta = required_param('delete', PARAM_INT);
    
    // ✅ Verificar que la ruta pertenece al usuario actual
    $ruta = $DB->get_record('learningstylesurvey_paths', ['id' => $idruta, 'userid' => $USER->id]);
    if (!$ruta) {
        print_error('No tienes permisos para eliminar esta ruta.');
    }
    
    $DB->delete_records('learningstylesurvey_path_files', ['pathid' => $idruta]);
    $DB->delete_records('learningstylesurvey_path_evaluations', ['pathid' => $idruta]);
    $DB->delete_records('learningstylesurvey_paths', ['id' => $idruta]);

    redirect(new moodle_url('/mod/learningstylesurvey/path/learningpath.php', [
        'courseid' => $courseid, 
        'cmid' => $cmid
    ]), "Ruta eliminada correctamente. Ahora puedes crear una nueva ruta.", 3);
    exit;
}

// Mostrar rutas disponibles - ✅ Solo las del usuario actual
$records = $DB->get_records('learningstylesurvey_paths', ['courseid' => $courseid, 'userid' => $USER->id]);

if (!$records) {
    echo "<p>No hay rutas registradas.</p>";
} else {
    echo "<ul>";
    foreach ($records as $ruta) {
        $deleteurl = new moodle_url('/mod/learningstylesurvey/path/delete_learningpath.php', [
            'courseid' => $courseid, 
            'delete' => $ruta->id, 
            'confirm' => 1, 
            'cmid' => $cmid
        ]);
        echo "<li><strong>{$ruta->name}</strong>";

        // Archivos
        $archivos = $DB->get_records('learningstylesurvey_path_files', ['pathid' => $ruta->id]);
        if ($archivos) {
            echo " - <strong>Archivos:</strong> ";
            $nombresArchivos = array_map(fn($f) => $f->filename, $archivos);
            echo implode(', ', $nombresArchivos);
        }

        // Evaluaciones
        $evaluaciones = $DB->get_records('learningstylesurvey_path_evaluations', ['pathid' => $ruta->id]);
        if ($evaluaciones) {
            echo " - <strong>Evaluaciones:</strong> ";
            $nombresEvaluaciones = [];
            foreach ($evaluaciones as $e) {
                $eval = $DB->get_record('learningstylesurvey_quizzes', ['id' => $e->quizid]);
                if ($eval) $nombresEvaluaciones[] = $eval->name;
            }
            echo implode(', ', $nombresEvaluaciones);
        }

        echo " <button onclick=\"confirmDelete('{$deleteurl}')\" class='btn btn-danger'>Eliminar</button></li>";
    }
    echo "</ul>";
}

// Botón de regreso
$urlreturn = new moodle_url('/mod/learningstylesurvey/path/learningpath.php', array('courseid' => $courseid, 'cmid' => $cmid));
echo "<a href='{$urlreturn}' class='btn btn-secondary'>Regresar</a>";

// Modal para confirmación personalizada
?>
<div id="customModal" style="display:none; position:fixed; top:30%; left:35%; background:white; border:1px solid black; padding:20px; z-index:1000;">
    <p>¿Estás seguro de que deseas eliminar esta ruta?</p>
    <button onclick="proceedDelete()" class="btn btn-danger">Eliminar</button>
    <button onclick="closeModal()" class="btn btn-secondary">Cancelar</button>
</div>
<script>
    let deleteUrl = '';
    function confirmDelete(url) {
        deleteUrl = url;
        document.getElementById('customModal').style.display = 'block';
    }
    function proceedDelete() {
        window.location.href = deleteUrl;
    }
    function closeModal() {
        document.getElementById('customModal').style.display = 'none';
        deleteUrl = '';
    }
</script>
<?php
echo $OUTPUT->footer();
