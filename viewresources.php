<?php
require_once("../../config.php");

$courseid = required_param("courseid", PARAM_INT);
require_login($courseid);

$context = context_course::instance($courseid);
$PAGE->set_context($context);
$PAGE->set_url("/mod/learningstylesurvey/viewresources.php", ["courseid" => $courseid]);
$PAGE->set_title("Material adaptativo");
$PAGE->set_heading("Material adaptativo para tu estilo de aprendizaje");

$cm = get_fast_modinfo($courseid)->get_instances_of('learningstylesurvey');
$firstcm = reset($cm);
$cmid = $firstcm->id;

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
        $DB->delete_records('learningstylesurvey_path_files', ['filename' => $resource->filename]);
        $DB->delete_records('learningpath_steps', ['resourceid' => $deleteid, 'istest' => 0]);

        // Eliminar registro principal
        $DB->delete_records('learningstylesurvey_resources', ['id' => $resource->id]);

        redirect(new moodle_url('/mod/learningstylesurvey/viewresources.php', ['courseid' => $courseid]), 'Recurso eliminado correctamente.', 1);
    }
}

// Obtener recursos del curso con nombre de tema
$resources = $DB->get_records_sql("
    SELECT r.*, t.tema AS nombretema
    FROM {learningstylesurvey_resources} r
    LEFT JOIN {learningstylesurvey_temas} t ON r.tema = t.id
    WHERE r.courseid = ?
    ORDER BY r.id DESC
", [$courseid]);

// Limpiar registros huérfanos (archivo no existe físicamente)
foreach ($resources as $res) {
    $filepath = __DIR__ . '/uploads/' . $res->filename;
    if (!is_file($filepath)) {
        $DB->delete_records('learningstylesurvey_resources', ['id' => $res->id]);
        unset($resources[$res->id]); // Eliminar del array actual
    }
}

// Evitar mostrar duplicados por filename
$seen = [];
$filteredResources = [];
foreach ($resources as $r) {
    if (!in_array($r->filename, $seen)) {
        $filteredResources[] = $r;
        $seen[] = $r->filename;
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading("Material adaptativo para tu estilo de aprendizaje");

if (empty($filteredResources)) {
    echo $OUTPUT->notification("No tienes material adaptativo disponible.", "notifymessage");
    echo $OUTPUT->footer();
    exit;
}

// Mostrar lista de recursos
echo "<ul style='list-style:none; padding:0;'>";
foreach ($filteredResources as $resource) {
    $filename = $resource->filename;
    $filepath = __DIR__ . '/uploads/' . $filename;
    $name = format_string($resource->name);
    $fileurl = "{$CFG->wwwroot}/mod/learningstylesurvey/uploads/{$filename}";
    $deleteurl = new moodle_url("/mod/learningstylesurvey/viewresources.php", [
        'deleteid' => $resource->id,
        'courseid' => $courseid
    ]);

    echo "<li style='margin-bottom:30px; padding:15px; border:1px solid #ddd; border-radius:8px; background:#f9f9f9;'>";
    echo "<h4 style='margin-bottom:10px;'>$name</h4>";
    // Mostrar nombre del tema asociado si existe
    if (!empty($resource->nombretema)) {
        echo "<p><strong>Tema:</strong> " . format_string($resource->nombretema) . "</p>";
    }

    // Botón Ver recurso (abre en visor interno)
    echo "<button class='btn btn-link' onclick=\"viewResource('$fileurl', '" . pathinfo($filename, PATHINFO_EXTENSION) . "')\">Ver recurso</button><br>";

    // Botón eliminar (confirmación simple)
    echo "<a href='{$deleteurl}' class='btn btn-danger' style='margin-top:10px;' onclick=\"return confirm('¿Seguro que deseas eliminar este recurso?')\">Eliminar recurso</a>";
    echo "</li>";
}
echo "</ul>";

// Botón para regresar al curso
echo html_writer::div(
    html_writer::link(new moodle_url('/mod/learningstylesurvey/view.php', ['id' => $cmid]), 'Regresar al curso', [
        'class' => 'btn btn-dark',
        'style' => 'margin-top: 30px;'
    ]),
    'regresar-curso'
);

// Visor interno
echo "<div id='viewer' style='display:none; margin-top:30px;'>
        <button class='btn btn-secondary' onclick='closeViewer()' style='margin-bottom:15px;'>Regresar al listado</button>
        <div id='viewer-content'></div>
      </div>";

echo $OUTPUT->footer();
?>

<script>
function viewResource(fileUrl, fileType) {
    document.getElementById('resource-list')?.remove();
    document.getElementById('viewer').style.display = 'block';

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

    document.getElementById('viewer-content').innerHTML = content;
}

function closeViewer() {
    location.reload(); // Volver a listado recargando la página
}
</script>
