<?php
require_once('../../config.php');

$courseid = required_param('courseid', PARAM_INT);
require_login($courseid);

$PAGE->set_url(new moodle_url('/mod/learningstylesurvey/learningpath.php', ['courseid' => $courseid]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Ruta de Aprendizaje');
$PAGE->set_heading('Ruta de Aprendizaje');

// Obtener cmid dinámico
$cm = get_fast_modinfo($courseid)->get_instances_of('learningstylesurvey');
$firstcm = reset($cm);
$cmid = $firstcm->id;

echo $OUTPUT->header();

// Obtener la primera ruta del curso (si existe)
global $DB;
$path = $DB->get_record('learningstylesurvey_paths', ['courseid' => $courseid], '*', IGNORE_MISSING);
$pathid = $path ? $path->id : 0;
?>

<div style="margin: 2rem;">
    <ul style="list-style-type: none; padding-left: 0;">
        <li style="margin-bottom: 1rem;">
            <a href="createsteproute.php?courseid=<?php echo $courseid; ?>">Crear Ruta de Aprendizaje</a>
        </li>

        <li style="margin-bottom: 1rem;">
            <?php if ($pathid): ?>
                <a href="edit_learningpath.php?courseid=<?php echo $courseid; ?>&id=<?php echo $pathid; ?>">Editar Ruta de Aprendizaje</a>
            <?php else: ?>
                <span style="color: gray;">No hay rutas para editar</span>
            <?php endif; ?>
        </li>

        <li style="margin-bottom: 1rem;">
            <?php if ($pathid): ?>
                <a href="delete_learningpath.php?courseid=<?php echo $courseid; ?>&id=<?php echo $pathid; ?>">Eliminar Ruta de Aprendizaje</a>
            <?php else: ?>
                <span style="color: gray;">No hay rutas para eliminar</span>
            <?php endif; ?>
        </li>

        <li style="margin-bottom: 1rem;">
            <?php if ($pathid): ?>
                <a href="organizar_ruta.php?courseid=<?php echo $courseid; ?>&pathid=<?php echo $pathid; ?>">Modificar Orden de la Ruta de Aprendizaje</a>
            <?php else: ?>
                <span style="color: gray;">No hay rutas para modificar</span>
            <?php endif; ?>
        </li>
    </ul>

    <!-- ✅ Botón de regreso con cmid dinámico -->
    <a href="<?php echo new moodle_url("/mod/learningstylesurvey/view.php", ["id" => $cmid]); ?>">
        <button style="background-color: #333; color: white; border: none; padding: 0.5rem 1rem; border-radius: 5px;">Regresar al Menú Anterior</button>
    </a>
</div>

<?php
echo $OUTPUT->footer();
?>
