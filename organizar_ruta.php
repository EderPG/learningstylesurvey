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


$ruta = $DB->get_record('learningstylesurvey_paths', ['id' => $pathid, 'userid' => $USER->id]);
if (!$ruta) {
    print_error('No tienes permisos para acceder a esta ruta.');
}
if (!$ruta) {
    echo "<div class='alert alert-danger'>Error: La ruta con ID $pathid no existe. No se pueden guardar los pasos.</div>";
    echo $OUTPUT->footer();
    exit;
}
$rutanombre = format_string($ruta->name);

echo "<h2>Organizar Ruta: $rutanombre</h2>";
echo "<p>Arrastra los pasos para cambiar el orden. Si es examen, define a qué paso redirigir según el resultado.</p>";

// Solo recrear pasos si no existen o si están incompletos
$existing_steps = $DB->get_records('learningpath_steps', ['pathid' => $pathid], 'stepnumber ASC');
$need_recreation = false;

// Verificar si necesitamos recrear los pasos
if (empty($existing_steps)) {
    $need_recreation = true;
} else {
    // Verificar que tengamos pasos para todos los temas y quizzes
    $temas_ruta = $DB->get_records('learningstylesurvey_path_temas', ['pathid' => $pathid], 'orden ASC');
    $total_recursos_esperados = 0;
    foreach ($temas_ruta as $temas_ruta_obj) {
        $recursos = $DB->get_records('learningstylesurvey_resources', ['tema' => $temas_ruta_obj->temaid]);
        $total_recursos_esperados += count($recursos);
    }
    $quizzes = $DB->get_records_sql("SELECT q.* FROM {learningstylesurvey_quizzes} q JOIN {learningstylesurvey_path_evaluations} pe ON pe.quizid = q.id WHERE pe.pathid = ?", [$pathid]);
    $total_esperado = $total_recursos_esperados + count($quizzes);
    
    if (count($existing_steps) < $total_esperado) {
        $need_recreation = true;
    }
}

$steps = [];
if ($need_recreation) {
    // Solo eliminar y recrear si es necesario
    $DB->delete_records('learningpath_steps', ['pathid' => $pathid]);
    
    // Crear pasos automáticamente usando los temas y recursos
    $temas_ruta = $DB->get_records('learningstylesurvey_path_temas', ['pathid' => $pathid], 'orden ASC');
    $temas_ids = array_map(function($t) { return $t->temaid; }, $temas_ruta);
    $temas = $temas_ids ? $DB->get_records_list('learningstylesurvey_temas', 'id', $temas_ids) : [];
    $stepnumber = 1;
    $total_insertados = 0;
    foreach ($temas_ruta as $temas_ruta_obj) {
        $temaid = $temas_ruta_obj->temaid;
        $recursos = $DB->get_records('learningstylesurvey_resources', ['tema' => $temaid]);
        foreach ($recursos as $recurso) {
            $step = new stdClass();
            $step->pathid = $pathid;
            $step->stepnumber = $stepnumber++;
            $step->resourceid = $recurso->id;
            $step->istest = 0;
            $step->passredirect = 0;
            $step->failredirect = 0;
            $DB->insert_record('learningpath_steps', $step);
            $total_insertados++;
        }
    }
    // Buscar quizzes asociados a la ruta
    $quizzes = $DB->get_records_sql("SELECT q.* FROM {learningstylesurvey_quizzes} q JOIN {learningstylesurvey_path_evaluations} pe ON pe.quizid = q.id WHERE pe.pathid = ?", [$pathid]);
    foreach ($quizzes as $quiz) {
        $step = new stdClass();
        $step->pathid = $pathid;
        $step->stepnumber = $stepnumber++;
        $step->resourceid = $quiz->id;
        $step->istest = 1;
        $step->passredirect = 0;
        $step->failredirect = 0;
        $DB->insert_record('learningpath_steps', $step);
        $total_insertados++;
    }
    $steps = $DB->get_records('learningpath_steps', ['pathid' => $pathid], 'stepnumber ASC');
    if ($total_insertados == 0) {
        echo "<div class='alert alert-warning'>No se encontraron recursos ni exámenes para esta ruta. La tabla de pasos está vacía.</div>";
    }
} else {
    // Usar pasos existentes sin recrear
    $steps = $existing_steps;
}

// Obtener los temas asociados a la ruta y su orden
$temas_ruta = $DB->get_records('learningstylesurvey_path_temas', ['pathid' => $pathid], 'orden ASC');
$temas_ids = array_map(function($t) { return $t->temaid; }, $temas_ruta);
$temas = $DB->get_records_list('learningstylesurvey_temas', 'id', $temas_ids);

// Crear un índice de temas_ruta por temaid para fácil acceso
$temas_ruta_by_id = [];
foreach ($temas_ruta as $tr) {
    $temas_ruta_by_id[$tr->temaid] = $tr;
}

$temas_pasos = [];
$temas_refuerzo = [];
foreach ($temas_ruta as $temas_ruta_obj) {
    $temaid = $temas_ruta_obj->temaid;
    $tema = $temas[$temaid];
    if (!empty($temas_ruta_obj->isrefuerzo)) {
        $temas_refuerzo[$temaid] = [
            'tema' => $tema,
            'recursos' => []
        ];
        continue;
    }
    if (!isset($temas_pasos[$temaid])) {
        $temas_pasos[$temaid] = [
            'tema' => $tema,
            'recursos' => [],
            'examenes' => []
        ];
    }
}
foreach ($steps as $step) {
    if (!$step->istest) {
        $resource = $DB->get_record('learningstylesurvey_resources', ['id' => $step->resourceid]);
        if ($resource && isset($temas_pasos[$resource->tema])) {
            $temas_pasos[$resource->tema]['recursos'][] = $step;
        }
        if ($resource && isset($temas_refuerzo[$resource->tema])) {
            $temas_refuerzo[$resource->tema]['recursos'][] = $step;
        }
    } else {
        $temas_pasos['examenes'][] = $step;
    }
}

echo "<div id='tarjetas' style='margin-top:20px;'>";

    // Mostrar tarjetas por tema (excluyendo refuerzo)
    foreach ($temas_pasos as $temaid => $grupo) {
        if ($temaid === 'examenes') continue;
        $tema = $grupo['tema'];
        // Buscar el registro de la asociación para este tema usando el índice
        $pathtema = isset($temas_ruta_by_id[$temaid]) ? $temas_ruta_by_id[$temaid] : null;
        $checked = ($pathtema && !empty($pathtema->isrefuerzo)) ? 'checked' : '';
        echo "<div class='card tema-card' data-id='{$temaid}' data-tipo='tema' style='background:white; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.1); margin-bottom:20px; padding:15px;'>";
        echo "<h3>" . format_string($tema->tema ?: 'Sin tema') . " <small style='color:gray;'>(tema)</small></h3>";
        echo "<label style='display:block; margin-bottom:10px;'><input type='checkbox' class='refuerzo-checkbox' data-temaid='{$temaid}' {$checked}> Tema de refuerzo</label>";
        echo "<div class='refuerzo-aviso' style='display:none; color:#e67e22; font-weight:bold; margin-bottom:10px;'>Este tema NO aparecerá en el flujo normal. Solo se mostrará si se programa un salto desde un examen (si reprueba el alumno).</div>";
        echo "<ul>";
        foreach ($grupo['recursos'] as $step) {
            $resource = $DB->get_record('learningstylesurvey_resources', ['id' => $step->resourceid]);
            $nombre = $resource ? format_string($resource->name ?: 'Sin nombre') : 'Sin nombre';
            $estilo = $resource ? format_string($resource->style ?: 'Sin estilo') : '';
            echo "<li>" . $nombre . " <span style='color: #888;'>(Estilo: $estilo)</span></li>";
        }
        echo "</ul>";
        echo "</div>";
    }

    // Mostrar tarjeta especial para temas de refuerzo
    foreach ($temas_refuerzo as $temaid => $grupo) {
        $tema = $grupo['tema'];
        echo "<div class='card tema-card' data-id='{$temaid}' data-tipo='tema' style='background:#f8f9fa; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.07); margin-bottom:20px; padding:15px; border:2px dashed #e67e22;'>";
        echo "<h3>" . format_string($tema->tema ?: 'Sin tema') . " <small style='color:#e67e22;'>(tema de refuerzo)</small></h3>";
        echo "<div style='color:#e67e22; font-weight:bold; margin-bottom:10px;'>Este tema NO aparecerá en el flujo normal. Solo se mostrará si se programa un salto desde un examen (si reprueba el alumno).</div>";
        echo "<ul>";
        foreach ($grupo['recursos'] as $step) {
            $resource = $DB->get_record('learningstylesurvey_resources', ['id' => $step->resourceid]);
            $nombre = $resource ? format_string($resource->name ?: 'Sin nombre') : 'Sin nombre';
            $estilo = $resource ? format_string($resource->style ?: 'Sin estilo') : '';
            echo "<li>" . $nombre . " <span style='color: #888;'>(Estilo: $estilo)</span></li>";
        }
        echo "</ul>";
        echo "</div>";
    }

    // Mostrar tarjeta de exámenes con selectores de salto
    if (!empty($temas_pasos['examenes'])) {
        foreach ($temas_pasos['examenes'] as $step) {
            $nombre = $DB->get_field('learningstylesurvey_quizzes', 'name', ['id' => $step->resourceid]);
            echo "<div class='card examen-card' data-id='{$step->id}' data-tipo='examen' style='background:white; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.1); margin-bottom:20px; padding:15px;'>";
            echo "<h3>Examen <small style='color:gray;'>(examen)</small></h3>";
            echo "<b>" . format_string($nombre ?: 'Sin nombre') . "</b>";
            // Selector para salto si aprueba
            echo "<div style='margin-top:8px;'>";
            echo "<label>Salto si aprueba: <select class='redirect-pass' style='margin-left:5px;'>";
            echo "<option value='0'>-- Siguiente por orden --</option>";
            
            // Mostrar todos los temas de la ruta como opciones de salto
            foreach ($temas_ruta as $temas_ruta_obj) {
                $temaid = $temas_ruta_obj->temaid;
                $tema = $temas[$temaid];
                $isrefuerzo = !empty($temas_ruta_obj->isrefuerzo);
                $label = format_string($tema->tema ?: 'Sin tema');
                if ($isrefuerzo) {
                    $label .= " (refuerzo)";
                }
                $selected = ($step->passredirect == $temaid) ? "selected" : "";
                echo "<option value='{$temaid}' {$selected}>{$label}</option>";
            }
            echo "</select></label>";
            echo "</div>";
            
            // Selector para salto si reprueba
            echo "<div style='margin-top:8px;'>";
            echo "<label>Salto si reprueba: <select class='redirect-fail' style='margin-left:5px;'>";
            echo "<option value='0'>-- Siguiente por orden --</option>";
            
            // Mostrar todos los temas de la ruta como opciones de salto
            foreach ($temas_ruta as $temas_ruta_obj) {
                $temaid = $temas_ruta_obj->temaid;
                $tema = $temas[$temaid];
                $isrefuerzo = !empty($temas_ruta_obj->isrefuerzo);
                $label = format_string($tema->tema ?: 'Sin tema');
                if ($isrefuerzo) {
                    $label .= " (refuerzo)";
                }
                $selected = ($step->failredirect == $temaid) ? "selected" : "";
                echo "<option value='{$temaid}' {$selected}>{$label}</option>";
            }
            echo "</select></label>";
            echo "</div>";
            echo "</div>";
        }
    }

    echo "</div>";
    echo "<button class='btn btn-primary' onclick='guardarOrden()' style='margin-top:20px;'>Guardar cambios</button>";


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

// Checkbox lógica para marcar tema de refuerzo
document.querySelectorAll('.refuerzo-checkbox').forEach(function(checkbox) {
    checkbox.addEventListener('change', function() {
        const card = this.closest('.tema-card');
        const aviso = card.querySelector('.refuerzo-aviso');
        if (this.checked) {
            card.style.background = '#f8f9fa';
            card.style.border = '2px dashed #e67e22';
            aviso.style.display = 'block';
        } else {
            card.style.background = 'white';
            card.style.border = '';
            aviso.style.display = 'none';
        }
    });
});

function guardarOrden() {
    const tarjetas = document.querySelectorAll('#tarjetas .card');
    const pathid = <?php echo (int)$pathid; ?>;
    const orden = Array.from(tarjetas).map((el, index) => {
        const id = el.getAttribute('data-id');
        const tipo = el.getAttribute('data-tipo');
        const passredirect = el.querySelector('.redirect-pass') ? el.querySelector('.redirect-pass').value : null;
        const failredirect = el.querySelector('.redirect-fail') ? el.querySelector('.redirect-fail').value : null;
        let isrefuerzo = false;
        const refuerzoCheckbox = el.querySelector('.refuerzo-checkbox');
        if (refuerzoCheckbox && refuerzoCheckbox.checked) {
            isrefuerzo = true;
        }
        return {
            id: id,
            tipo: tipo,
            orden: index + 1,
            passredirect: passredirect,
            failredirect: failredirect,
            isrefuerzo: isrefuerzo,
            pathid: pathid
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
