<?php
require_once('../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$context = context_course::instance($courseid);
require_login($courseid);
$PAGE->set_context($context);
$PAGE->set_url('/mod/learningstylesurvey/crear_ruta_aprendizaje.php', ['courseid' => $courseid]);
$PAGE->set_title('Crear Ruta de Aprendizaje');
$PAGE->set_heading('Crear Ruta de Aprendizaje');

echo $OUTPUT->header();
?>

<div style="margin: 40px;">
        <form action="guardar_ruta.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="courseid" value="<?php echo $courseid; ?>">

        <div style="margin-bottom: 15px;">
            <label for="nombre">Nombre de la Ruta:</label><br>
            <input type="text" name="nombre" id="nombre" style="width: 250px; padding: 6px;" required>
        </div>

        <div style="margin-bottom: 15px;">
            <label for="archivo">Archivo</label><br>
            <input type="file" name="archivo" id="archivo" style="padding: 6px;" required>
        </div>

        <div style="margin-bottom: 15px;">
            <label for="indicaciones">Indicaciones:</label><br>
            <input type="text" name="indicaciones" id="indicaciones" style="width: 250px; padding: 6px;">
        </div>

        <div>
            <button type="submit" style="padding: 8px 20px; background-color: #333; color: white; border: none;">Guardar Ruta</button>
            <a href="learningpath.php?courseid=<?php echo $courseid; ?>" style="padding: 8px 20px; background-color: #444; color: white; text-decoration: none; margin-left: 10px;">Regresar</a>
        </div>
    </form>
</div>

<?php
echo $OUTPUT->footer();
?>
