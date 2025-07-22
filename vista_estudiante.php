<?php
require_once("../../config.php");
global $DB, $USER, $PAGE, $OUTPUT;

$courseid = required_param('courseid', PARAM_INT);
$pathid = optional_param('pathid', null, PARAM_INT);

if (!$pathid) {
    $path = $DB->get_record('learningstylesurvey_paths', ['courseid' => $courseid], '*', IGNORE_MISSING);
    if (!$path) {
        print_error('No se encontró ninguna ruta para este curso.');
    }
    $pathid = $path->id;
}

require_login($courseid);
$context = context_course::instance($courseid);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/mod/learningstylesurvey/vista_estudiante.php', ['courseid' => $courseid, 'pathid' => $pathid]));
$PAGE->set_title("Ruta de Aprendizaje");
$PAGE->set_heading("Ruta de Aprendizaje");

echo $OUTPUT->header();
echo "<div class='container' style='max-width:1000px; margin:30px auto; padding:15px;'>";

$ruta = $DB->get_record('learningstylesurvey_paths', ['id' => $pathid], '*', IGNORE_MISSING);
$rutanombre = $ruta ? format_string($ruta->name) : 'Ruta sin nombre';

echo "<h2>Ruta de Aprendizaje: $rutanombre</h2>";

if (!is_role_switched($courseid) && !isguestuser() && !is_enrolled($context, $USER->id, 'student')) {
    echo "<div id='editindicator' style='margin-bottom: 10px; font-weight: bold; color: #007bff; display: none;'>Modo edición activo</div>";
    echo "<button class='btn' onclick='toggleEditMode()'>Editar orden</button>";
    echo "<button class='btn' onclick='guardarOrden()'>Guardar nuevo orden</button>";
}

echo "<div id='tarjetas'>";

// ✅ CONSULTA CORREGIDA: usar tabla learningstylesurvey_resources
$resources = $DB->get_records_sql("
    SELECT DISTINCT r.id, r.filename, r.instructions, pf.steporder as orden, 'recurso' as tipo
    FROM {learningstylesurvey_inforoute} r
    JOIN {learningstylesurvey_path_files} pf ON pf.filename = r.filename
    WHERE pf.pathid = ?
    ORDER BY pf.steporder ASC
", [$pathid]);

$quizzes = $DB->get_records_sql("
    SELECT q.id, q.name as filename, pe.steporder as orden, 'examen' as tipo
    FROM {learningstylesurvey_quizzes} q
    JOIN {learningstylesurvey_path_evaluations} pe ON pe.quizid = q.id
    WHERE pe.pathid = ?
    ORDER BY pe.steporder ASC", [$pathid]);

$elementos = array_merge($resources, $quizzes);
usort($elementos, fn($a, $b) => $a->orden <=> $b->orden);

foreach ($elementos as $el) {
    echo "<div class='card' data-id='{$el->id}' data-tipo='{$el->tipo}' style='background:white; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.1); margin-bottom:25px; padding:20px;'>";
    echo "<h3>" . format_string($el->filename) . "</h3>";

    if ($el->tipo === 'recurso') {
        $filepath = "/mod/learningstylesurvey/uploads/" . $el->filename;
        $fileurl = new moodle_url($filepath);

        // ✅ Solo mostrar el nombre como enlace clickeable
        $verurl = new moodle_url('/mod/learningstylesurvey/ver_recurso.php', [
            'filename' => $el->filename,
            'courseid' => $courseid
        ]);
        echo "<p><strong>Archivo:</strong> <a href='{$verurl}'>{$el->filename}</a></p>";
        
    } else {
        $result = $DB->get_record('learningstylesurvey_quiz_results', [
            'userid' => $USER->id,
            'quizid' => $el->id,
            'courseid' => $courseid
        ]);

        $estatus = "";
        if ($result) {
            $estatus = $result->score >= 70 ? "<span style='color:green; font-weight:bold;'>¡Aprobado!</span>" : "<span style='color:red; font-weight:bold;'>Reprobado</span>";
            $estatus .= " <span style='font-weight:bold;'>({$result->score}%)</span>";
        }

        echo "<div style='margin:10px 0;'>$estatus</div>";
        echo "<a class='btn' href='responder_quiz.php?id={$el->id}&courseid={$courseid}' style='background-color:#28a745; color:white; padding:10px 15px; border-radius:5px;'>Responder examen</a>";
    }

    echo "</div>";
}


echo "</div>";

$modinfo = get_fast_modinfo($courseid);
$cmid = null;
foreach ($modinfo->get_cms() as $cm) {
    if ($cm->modname === 'learningstylesurvey') {
        $cmid = $cm->id;
        break;
    }
}
if ($cmid) {
    $viewurl = new moodle_url('/mod/learningstylesurvey/view.php', array('id' => $cmid));
    echo "<a href='{$viewurl}' class='btn' style='background-color:#6c757d; color:white; padding:10px 15px; border-radius:5px; text-decoration:none;'>Regresar al menú anterior</a>";
}

echo "</div>";
?>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let editMode = false;
let sortable = null;

function toggleEditMode() {
    if (!editMode) {
        sortable = Sortable.create(document.getElementById('tarjetas'), {
            animation: 150
        });
        editMode = true;
        document.getElementById('editindicator').style.display = 'block';
    } else {
        if (sortable) sortable.destroy();
        editMode = false;
        document.getElementById('editindicator').style.display = 'none';
    }
}

function guardarOrden() {
    const tarjetas = document.querySelectorAll('#tarjetas .card');
    const orden = Array.from(tarjetas).map((el, index) => ({
        id: el.getAttribute('data-id'),
        tipo: el.getAttribute('data-tipo'),
        orden: index,
        pathid: <?php echo $pathid; ?>
    }));

    fetch('guardar_orden.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(orden)
    })
    .then(response => response.text())
    .then(data => {
        Swal.fire({
            icon: 'success',
            title: 'Orden actualizado',
            text: 'El orden de los elementos se ha guardado correctamente',
            timer: 2000,
            showConfirmButton: false
        });
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Hubo un problema al guardar el nuevo orden'
        });
    });
}
</script>

<?php
echo $OUTPUT->footer();
?>