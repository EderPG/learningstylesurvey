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

// Eliminar recurso si se recibe el parámetro
$deleteid = optional_param('deleteid', 0, PARAM_INT);
if ($deleteid > 0) {
    $resource = $DB->get_record('learningstylesurvey_resources', ['id' => $deleteid, 'courseid' => $courseid]);
    if ($resource) {
        $filepath = __DIR__ . '/uploads/' . $resource->filename;

        // Borra archivo físico si existe
        if (is_file($filepath)) {
            unlink($filepath);
        }

        // Elimina la entrada en la tabla de recursos
        $DB->delete_records('learningstylesurvey_resources', ['id' => $resource->id]);

        // Redirecciona con mensaje
        redirect(new moodle_url('/mod/learningstylesurvey/viewresources.php', ['courseid' => $courseid]), 'Recurso eliminado.', 1);
    }
}

// Obtener todos los recursos del curso
$rawresources = $DB->get_records("learningstylesurvey_resources", ["courseid" => $courseid]);

// Filtrar duplicados por nombre de archivo
$seen = [];
$resources = [];
foreach ($rawresources as $r) {
    if (!in_array($r->filename, $seen)) {
        $resources[] = $r;
        $seen[] = $r->filename;
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading("Material adaptativo para tu estilo de aprendizaje");

if (empty($resources)) {
    echo $OUTPUT->notification("No tienes material adaptativo disponible.", "notifymessage");
    echo $OUTPUT->footer();
    exit;
}

echo html_writer::start_tag("ul", ['style' => 'list-style:none; padding:0;']);
foreach ($resources as $resource) {
    $filename = $resource->filename;
    $filepath = __DIR__ . '/uploads/' . $filename;
    $name = format_string($resource->name);

    // Mostrar mensaje si el archivo no existe
    if (!file_exists($filepath)) {
        echo html_writer::tag("li", "<strong>$name:</strong> Archivo no encontrado", ['style' => 'color:red']);
        continue;
    }

    $fileurl = new moodle_url("/mod/learningstylesurvey/uploads/" . $filename);
    $deleteurl = new moodle_url("/mod/learningstylesurvey/viewresources.php", [
        'deleteid' => $resource->id,
        'courseid' => $courseid
    ]);

    echo "<li style='margin-bottom:30px;'>";
    echo "<h4>$name</h4>";
    echo "<a href='{$fileurl}' target='_self' style='text-decoration:underline;'>{$filename}</a>";

    echo html_writer::div(
        html_writer::link($deleteurl, 'Eliminar recurso', [
            'class' => 'btn btn-danger',
            'onclick' => "return confirm('¿Estás seguro de que deseas eliminar este recurso?');"
        ]),
        'boton-eliminar',
        ['style' => 'margin-top: 10px;']
    );

    echo "</li>";
}
echo html_writer::end_tag("ul");

echo html_writer::div(
    html_writer::link(new moodle_url('/mod/learningstylesurvey/view.php', ['id' => $cmid]), 'Regresar al curso', ['class' => 'btn btn-dark', 'style' => 'margin-top: 30px;']),
    'regresar-curso'
);

echo $OUTPUT->footer();
