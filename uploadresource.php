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

        // ✅ Verificar si ya existe en la BD para este curso
        $existing = $DB->get_record('learningstylesurvey_resources', ['filename' => $filename, 'courseid' => $courseid]);

        if ($existing) {
            // ✅ Si el archivo existe en BD pero no físicamente, permitir re-subida y actualizar
            if (!file_exists($fullpath)) {
                if (move_uploaded_file($file['tmp_name'], $fullpath)) {
                    // Actualizar registro existente en lugar de duplicar
                    $existing->name = $name;
                    $existing->style = $style;
                    $existing->timecreated = time();
                    $DB->update_record('learningstylesurvey_resources', $existing);

                    $success = true;
                } else {
                    $errors[] = "Error al subir el archivo.";
                }
            } else {
                $errors[] = "Este archivo ya está registrado. Si deseas actualizarlo, primero elimínalo desde la lista de recursos.";
            }
        } else {
            // ✅ Si no existe en la BD, subir archivo e insertar
            if (file_exists($fullpath)) {
                $errors[] = "Ya existe un archivo físico con ese nombre en el sistema. Cambia el nombre antes de subirlo.";
            } else {
                if (move_uploaded_file($file['tmp_name'], $fullpath)) {
                    // ✅ Insertar en tabla resources
                    $record = new stdClass();
                    $record->courseid = $courseid;
                    $record->name = $name;
                    $record->style = $style;
                    $record->filename = $filename;
                    $record->timecreated = time();
                    $resourceid = $DB->insert_record('learningstylesurvey_resources', $record);

                    // ✅ Insertar en inforoute solo si no existe
                    if (!$DB->record_exists('learningstylesurvey_inforoute', ['filename' => $filename, 'courseid' => $courseid])) {
                        $route = new stdClass();
                        $route->courseid = $courseid;
                        $route->name = $name;
                        $route->filename = $filename;
                        $route->instructions = '';
                        $route->steporder = 0;
                        $route->style = $style;
                        $route->timecreated = time();
                        $route->resourceid = $resourceid;
                        $DB->insert_record('learningstylesurvey_inforoute', $route);
                    }

                    // ✅ Insertar en path_files solo si hay ruta y no existe duplicado
                    $path = $DB->get_record('learningstylesurvey_paths', ['courseid' => $courseid], '*', IGNORE_MISSING);
                    if ($path && !$DB->record_exists('learningstylesurvey_path_files', ['filename' => $filename, 'pathid' => $path->id])) {
                        $pathfile = new stdClass();
                        $pathfile->pathid = $path->id;
                        $pathfile->filename = $filename;
                        $pathfile->steporder = 0;
                        $DB->insert_record('learningstylesurvey_path_files', $pathfile);
                    }

                    // ✅ Insertar en learningpath_steps si no existe
                    if ($path && !$DB->record_exists('learningpath_steps', ['resourceid' => $resourceid, 'pathid' => $path->id])) {
                        $maxstep = $DB->get_field_sql("SELECT MAX(stepnumber) FROM {learningpath_steps} WHERE pathid = ?", [$path->id]);
                        $nextstep = $maxstep ? $maxstep + 1 : 1;

                        $step = new stdClass();
                        $step->pathid = $path->id;
                        $step->stepnumber = $nextstep;
                        $step->resourceid = $resourceid;
                        $step->istest = 0; // recurso
                        $DB->insert_record('learningpath_steps', $step);
                    }

                    $success = true;
                } else {
                    $errors[] = "Error al subir el archivo.";
                }
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
