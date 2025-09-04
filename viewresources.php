<?php
require_once("../../config.php");

$courseid = required_param("courseid", PARAM_INT);
$cmid = optional_param("cmid", 0, PARAM_INT); // ID de la instancia específica
require_login($courseid);

$context = context_course::instance($courseid);
$PAGE->set_context($context);
$PAGE->set_url("/mod/learningstylesurvey/viewresources.php", ["courseid" => $courseid, "cmid" => $cmid]);
$PAGE->set_title("Material adaptativo");
$PAGE->set_heading("Material adaptativo subido");

// Usar el cmid correcto si se proporcionó, sino buscar la primera instancia
if ($cmid > 0) {
    $targetcmid = $cmid;
} else {
    $cm = get_fast_modinfo($courseid)->get_instances_of('learningstylesurvey');
    $firstcm = reset($cm);
    $targetcmid = $firstcm->id;
}

// Acción para eliminar recurso (confirmación básica con window.confirm)
$deleteid = optional_param('deleteid', 0, PARAM_INT);
if ($deleteid > 0) {
    $resource = $DB->get_record('learningstylesurvey_resources', ['id' => $deleteid, 'courseid' => $courseid]);
    if ($resource) {
        $filepath = __DIR__ . '/uploads/' . $resource->filename;

        // Eliminar archivo físico
        if (is_file($filepath)) {
            unlink($filepath);
        }

        // Eliminar dependencias en otras tablas
        $DB->delete_records('learningstylesurvey_inforoute', ['filename' => $resource->filename, 'courseid' => $courseid]);
        $DB->delete_records('learningpath_steps', ['resourceid' => $deleteid, 'istest' => 0]);

        // Eliminar registro principal
        $DB->delete_records('learningstylesurvey_resources', ['id' => $resource->id]);
    }
}

// Obtener temas para el filtro - ✅ Solo los del usuario actual
$temas = $DB->get_records('learningstylesurvey_temas', ['courseid' => $courseid, 'userid' => $USER->id], 'timecreated DESC');
$selected_tema = optional_param('tema', '', PARAM_INT);

// Consulta de recursos filtrados por tema si se selecciona - SOLO del usuario actual
if ($selected_tema) {
    $resources = $DB->get_records_sql("
        SELECT r.*, t.tema AS nombretema
        FROM {learningstylesurvey_resources} r
        LEFT JOIN {learningstylesurvey_temas} t ON r.tema = t.id
        WHERE r.courseid = ? AND r.tema = ? AND r.userid = ?
        ORDER BY r.id DESC
    ", [$courseid, $selected_tema, $USER->id]);
} else {
    $resources = $DB->get_records_sql("
        SELECT r.*, t.tema AS nombretema
        FROM {learningstylesurvey_resources} r
        LEFT JOIN {learningstylesurvey_temas} t ON r.tema = t.id
        WHERE r.courseid = ? AND r.userid = ?
        ORDER BY r.id DESC
    ", [$courseid, $USER->id]);
}

// Limpiar registros huérfanos (archivo no existe físicamente)
foreach ($resources as $res) {
    $filepath = __DIR__ . '/uploads/' . $res->filename;
    if (!is_file($filepath)) {
        $DB->delete_records('learningstylesurvey_resources', ['id' => $res->id]);
        unset($resources[$res->id]); // Eliminar del array actual
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading("Material adaptativo");

if (empty($resources)) {
    echo $OUTPUT->notification("No tienes material adaptativo disponible.", "notifymessage");
    // Botón para regresar al curso SIEMPRE visible
    echo html_writer::div(
        html_writer::link(new moodle_url('/mod/learningstylesurvey/view.php', ['id' => $targetcmid]), 'Regresar al menu', [
            'class' => 'btn btn-dark',
            'style' => 'margin-top: 30px;'
        ]),
        'regresar-curso'
    );
    echo $OUTPUT->footer();
    exit;
}

// Formulario de filtro por tema
echo '<form method="get" style="max-width:400px; margin-bottom:30px;">';
echo '<input type="hidden" name="courseid" value="' . $courseid . '">';
echo '<label for="tema"><strong>Filtrar por tema:</strong></label> ';
echo '<select name="tema" id="tema" class="form-control" style="display:inline-block; width:auto; margin-right:10px;">';
echo '<option value="">Todos</option>';
foreach ($temas as $t) {
    $selected = ($selected_tema == $t->id) ? 'selected' : '';
    echo '<option value="' . $t->id . '" ' . $selected . '>' . format_string($t->tema) . '</option>';
}
echo '</select>';
echo '<button type="submit" class="btn btn-primary">Filtrar</button>';
echo '</form>';

    if (empty($resources)) {
        echo $OUTPUT->notification("No tienes material adaptativo disponible para el tema seleccionado.", "notifymessage");
        echo html_writer::div(
            html_writer::link(new moodle_url('/mod/learningstylesurvey/view.php', ['id' => $targetcmid]), 'Regresar al menu', [
                'class' => 'btn btn-dark',
                'style' => 'margin-top: 30px;'
            ]),
            'regresar-curso'
        );
        echo $OUTPUT->footer();
        exit;
    }

    // Mostrar todos los archivos en una lista desplegable tipo acordeón
    // Agrupar archivos por tema y mostrar una sola tarjeta por tema con lista de archivos
    $temas_archivos = [];
    foreach ($resources as $resource) {
        $tema_id = $resource->tema;
        if (!isset($temas_archivos[$tema_id])) {
            $temas_archivos[$tema_id] = [
                'nombretema' => !empty($resource->nombretema) ? format_string($resource->nombretema) : 'Sin tema',
                'archivos' => []
            ];
        }
        $temas_archivos[$tema_id]['archivos'][] = $resource;
    }

    echo "<div id='resource-list' style='max-width:700px; margin:0 auto;'>";
    $panelIdx = 0;
    foreach ($temas_archivos as $tema_id => $tema) {
        $panelId = 'panel_tema_' . $panelIdx;
        echo "<div style='border:1px solid #eee; border-radius:6px; background:#fff; margin-bottom:10px;'>";
        echo "<button class='btn btn-block' style='width:100%; text-align:left; padding:10px; font-weight:bold; background:#f5f5f5; border:none; border-radius:6px 6px 0 0;' onclick=\"togglePanel('$panelId')\">" . htmlspecialchars($tema['nombretema']) . "</button>";
        echo "<div id='$panelId' style='display:none; padding:15px;'>";
        echo "<ul style='list-style:none; padding:0;'>";
        foreach ($tema['archivos'] as $idx => $resource) {
            $filename = $resource->filename;
            $name = format_string($resource->name);
            $fileurl = "{$CFG->wwwroot}/mod/learningstylesurvey/uploads/{$filename}";
            $deleteurl = new moodle_url("/mod/learningstylesurvey/viewresources.php", [
                'deleteid' => $resource->id,
                'courseid' => $courseid
            ]);
            $viewerId = 'viewer-' . $panelId . '-' . $idx;
            echo "<li style='margin-bottom:10px; padding:8px; border-bottom:1px solid #eee;'>";
            echo "<div style='display:flex; align-items:center; justify-content:space-between;'>";
            echo "<div>";
            echo "<strong>$name</strong>";
            if (!empty($resource->style)) {
                echo "<span style='margin-left:10px; color:#888;'>Estilo: " . format_string($resource->style) . "</span>";
            }
            echo "</div>";
            echo "<div>";
            echo "<button class='btn btn-link' onclick=\"viewResource('$fileurl', '" . pathinfo($filename, PATHINFO_EXTENSION) . "', '$viewerId')\">Ver recurso</button>";
            echo "<a href='{$deleteurl}' class='btn btn-danger' style='margin-left:10px;' onclick=\"return confirm('¿Seguro que deseas eliminar este recurso?')\">Eliminar</a>";
            echo "</div>";
            echo "</div>";
            echo "<div id='$viewerId'></div>";
            echo "</li>";
        }
        echo "</ul>";
        echo "</div>";
        echo "</div>";
        $panelIdx++;
    }
    echo "</div>";


// Botón para regresar al curso
echo html_writer::div(
    html_writer::link(new moodle_url('/mod/learningstylesurvey/view.php', ['id' => $targetcmid]), 'Regresar al menú', [
        'class' => 'btn btn-dark',
        'style' => 'margin-top: 30px;'
    ]),
    'regresar-curso'
);

echo $OUTPUT->footer();
?>

<script>
function togglePanel(panelId) {
    var panel = document.getElementById(panelId);
    if (panel.style.display === 'none') {
        panel.style.display = 'block';
    } else {
        panel.style.display = 'none';
    }
}

function viewResource(fileUrl, fileType, viewerId) {
    var viewer = document.getElementById(viewerId);
    if (viewer.style.display === 'block') {
        viewer.innerHTML = '';
        viewer.style.display = 'none';
    } else {
        let content = '';
        fileType = fileType.toLowerCase();
        if (['jpg', 'jpeg', 'png', 'gif'].includes(fileType)) {
            content = `<img src="${fileUrl}" style="max-width:100%; height:auto;">`;
        } else if (fileType === 'pdf') {
            content = `<iframe src="${fileUrl}" style="width:100%; height:600px; border:none;"></iframe>`;
        } else if (['mp4', 'webm'].includes(fileType)) {
            content = `<video controls style="width:100%; max-height:500px;">
                          <source src="${fileUrl}" type="video/${fileType}">
                       Tu navegador no soporta video HTML5.
                       </video>`;
        } else {
            content = `<a href="${fileUrl}" target="_blank">Descargar recurso</a>`;
        }
        viewer.innerHTML = content;
        viewer.style.display = 'block';
    }
}
</script>
