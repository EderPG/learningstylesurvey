<?php
require_once("../../config.php");
require_login();

$courseid = required_param("courseid", PARAM_INT);

$context = context_course::instance($courseid);
$PAGE->set_context($context);
$PAGE->set_url("/mod/learningstylesurvey/uploadresource.php", ["courseid" => $courseid]);
$PAGE->set_title("Subir recurso adaptativo");
$PAGE->set_heading("Subir recurso adaptativo");

$cm = get_fast_modinfo($courseid)->get_instances_of('learningstylesurvey');
$firstcm = reset($cm);
$cmid = $firstcm->id;

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = required_param('name', PARAM_TEXT);
    $style = required_param('style', PARAM_TEXT);
    $file = $_FILES['file'];

    if (empty($name) || empty($style) || empty($file['name'])) {
        $errors[] = "Todos los campos son obligatorios.";
    } else {
        $upload_dir = __DIR__ . '/uploads/';
        $filename = basename($file['name']);
        $fullpath = $upload_dir . $filename;

        if (file_exists($fullpath)) {
            $errors[] = "Ya existe un archivo con ese nombre. Elimínalo antes de volver a subirlo.";
        } else {
            if (move_uploaded_file($file['tmp_name'], $fullpath)) {
                // Insertar en resources
                $record = new stdClass();
                $record->courseid = $courseid;
                $record->name = $name;
                $record->style = $style;
                $record->filename = $filename;
                $record->timecreated = time();
                $DB->insert_record('learningstylesurvey_resources', $record);

                // Insertar también en inforoute (para vista estudiante)
                $route = new stdClass();
                $route->courseid = $courseid;
                $route->name = $name;
                $route->filename = $filename;
                $route->instructions = '';
                $route->steporder = 0;
                $route->style = $style;
                $route->timecreated = time();
                $route->resourceid = 0;
                $DB->insert_record('learningstylesurvey_inforoute', $route);

                // ✅ NUEVO: Insertar en path_files para que aparezca en la ruta del estudiante
                $path = $DB->get_record('learningstylesurvey_paths', ['courseid' => $courseid], '*', IGNORE_MISSING);
                if ($path) {
                    $pathfile = new stdClass();
                    $pathfile->pathid = $path->id;
                    $pathfile->filename = $filename;
                    $pathfile->steporder = 0;
                    $DB->insert_record('learningstylesurvey_path_files', $pathfile);
                }

                $success = true;
            } else {
                $errors[] = "Error al subir el archivo.";
            }
        }
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading("Subir recurso adaptativo");

if (!empty($errors)) {
    foreach ($errors as $e) {
        echo $OUTPUT->notification($e, 'notifyproblem');
    }
}

if ($success) {
    echo $OUTPUT->notification("Archivo subido exitosamente.", 'notifysuccess');
}
?>

<form method="post" enctype="multipart/form-data" style="max-width: 600px; margin: 0 auto;">
    <div style="margin-bottom: 15px;">
        <label for="name"><strong>Nombre del recurso:</strong></label><br>
        <input type="text" id="name" name="name" class="form-control" required>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="style"><strong>Estilo de aprendizaje:</strong></label><br>
        <select id="style" name="style" class="form-control" required>
            <option value="activo">Activo</option>
            <option value="reflexivo">Reflexivo</option>
            <option value="sensorial">Sensorial</option>
            <option value="intuitivo">Intuitivo</option>
            <option value="visual">Visual</option>
            <option value="verbal">Verbal</option>
            <option value="secuencial">Secuencial</option>
            <option value="global">Global</option>
        </select>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="file"><strong>Archivo:</strong></label><br>
        <input type="file" id="file" name="file" class="form-control" required>
    </div>

    <div style="text-align: center;">
        <button type="submit" class="btn btn-primary">Subir</button>
    </div>
</form>
<div style="text-align: center; margin-top: 30px;">
    <a href="view.php?id=<?= $cmid ?>&courseid=<?= $courseid ?>" class="btn btn-secondary" style="padding:10px 15px; border-radius:5px;">
        Regresar al menú
    </a>
</div>

<?php
echo $OUTPUT->footer();