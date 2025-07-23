<?php
require_once("../../config.php");
global $DB, $USER, $PAGE, $OUTPUT;

$courseid = required_param('courseid', PARAM_INT);
$pathid = required_param('pathid', PARAM_INT);

require_login($courseid);
$context = context_course::instance($courseid);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/mod/learningstylesurvey/organizar_ruta.php', ['courseid' => $courseid, 'pathid' => $pathid]));
$PAGE->set_title("Organizar Ruta");
$PAGE->set_heading("Organizar Ruta");

echo $OUTPUT->header();
echo "<div class='container' style='max-width:1000px; margin:30px auto; padding:15px;'>";

$ruta = $DB->get_record('learningstylesurvey_paths', ['id' => $pathid]);
$rutanombre = $ruta ? format_string($ruta->name) : 'Ruta sin nombre';

echo "<h2>Organizar Ruta: $rutanombre</h2>";
echo "<p>Arrastra los pasos para cambiar el orden. Si es examen, define a qué paso redirigir según el resultado.</p>";

// ✅ Obtener pasos de la ruta
$steps = $DB->get_records('learningpath_steps', ['pathid' => $pathid], 'stepnumber ASC');

if (!$steps) {
    echo "<p>No hay pasos definidos para esta ruta.</p>";
} else {
    echo "<div id='tarjetas' style='margin-top:20px;'>";

    foreach ($steps as $step) {
        $tipo = $step->istest ? 'examen' : 'recurso';
        $nombre = $step->istest
            ? $DB->get_field('learningstylesurvey_quizzes', 'name', ['id' => $step->resourceid])
            : $DB->get_field('learningstylesurvey_resources', 'name', ['id' => $step->resourceid]);

        // Mostrar tarjeta
        echo "<div class='card' data-id='{$step->id}' data-tipo='{$tipo}' style='background:white; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.1); margin-bottom:20px; padding:15px;'>";
        echo "<h3>" . format_string($nombre ?: 'Sin nombre') . " <small style='color:gray;'>($tipo)</small></h3>";

        // ✅ Si es examen, mostrar selects para redirección
        if ($tipo === 'examen') {
            echo "<div style='margin-top:10px;'>";
            echo "<label><strong>Si aprueba ir a:</strong></label> ";
            echo "<select class='redirect-pass' data-id='{$step->id}'>";
            echo "<option value=''>-- Ninguno --</option>";

            foreach ($steps as $s) {
                if ($s->id != $step->id) {
                    $sname = $s->istest
                        ? $DB->get_field('learningstylesurvey_quizzes', 'name', ['id' => $s->resourceid])
                        : $DB->get_field('learningstylesurvey_resources', 'name', ['id' => $s->resourceid]);
                    $selected = ($step->passredirect == $s->id) ? 'selected' : '';
                    echo "<option value='{$s->id}' $selected>" . format_string($sname ?: 'Sin nombre') . "</option>";
                }
            }
            echo "</select>";

            echo " &nbsp; <label><strong>Si reprueba ir a:</strong></label> ";
            echo "<select class='redirect-fail' data-id='{$step->id}'>";
            echo "<option value=''>-- Ninguno --</option>";

            foreach ($steps as $s) {
                if ($s->id != $step->id) {
                    $sname = $s->istest
                        ? $DB->get_field('learningstylesurvey_quizzes', 'name', ['id' => $s->resourceid])
                        : $DB->get_field('learningstylesurvey_resources', 'name', ['id' => $s->resourceid]);
                    $selected = ($step->failredirect == $s->id) ? 'selected' : '';
                    echo "<option value='{$s->id}' $selected>" . format_string($sname ?: 'Sin nombre') . "</option>";
                }
            }
            echo "</select>";
            echo "</div>";
        }

        echo "</div>";
    }

    echo "</div>";
    echo "<button class='btn btn-primary' onclick='guardarOrden()' style='margin-top:20px;'>Guardar cambios</button>";
}

// Botón para volver al menú
$modinfo = get_fast_modinfo($courseid);
$cmid = null;
foreach ($modinfo->get_cms() as $cm) {
    if ($cm->modname === 'learningstylesurvey') {
        $cmid = $cm->id;
        break;
    }
}
if ($cmid) {
    $viewurl = new moodle_url('/mod/learningstylesurvey/view.php', ['id' => $cmid]);
    echo "<br><br><a href='{$viewurl}' class='btn btn-secondary' style='padding:10px 15px; border-radius:5px;'>Regresar al menú anterior</a>";
}

echo "</div>";
?>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let sortable = Sortable.create(document.getElementById('tarjetas'), {
    animation: 150
});

function guardarOrden() {
    const tarjetas = document.querySelectorAll('#tarjetas .card');
    const orden = Array.from(tarjetas).map((el, index) => {
        const id = el.getAttribute('data-id');
        const tipo = el.getAttribute('data-tipo');
        const passredirect = el.querySelector('.redirect-pass') ? el.querySelector('.redirect-pass').value : null;
        const failredirect = el.querySelector('.redirect-fail') ? el.querySelector('.redirect-fail').value : null;

        return {
            id: id,
            tipo: tipo,
            orden: index + 1,
            passredirect: passredirect,
            failredirect: failredirect
        };
    });

    fetch('guardar_orden.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(orden)
    })
    .then(response => response.json())
    .then(data => {
        Swal.fire({
            icon: 'success',
            title: 'Cambios guardados',
            text: 'El orden y los redireccionamientos se han actualizado correctamente.',
            timer: 2000,
            showConfirmButton: false
        });
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo guardar el nuevo orden.'
        });
    });
}
</script>

<?php
echo $OUTPUT->footer();
?>
