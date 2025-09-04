<?php
require_once('../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$cmid = optional_param('cmid', 0, PARAM_INT);

require_login($courseid);

// Si no se proporciona cmid, obtenerlo del contexto actual o usar el primero disponible
if (!$cmid) {
    $modinfo = get_fast_modinfo($courseid);
    $cms = $modinfo->get_instances_of('learningstylesurvey');
    if (!empty($cms)) {
        $firstcm = reset($cms);
        $cmid = $firstcm->id;
    }
}

$PAGE->set_url(new moodle_url('/mod/learningstylesurvey/learningpath.php', ['courseid' => $courseid, 'cmid' => $cmid]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Ruta de Aprendizaje');
$PAGE->set_heading('Ruta de Aprendizaje');

echo $OUTPUT->header();

// Obtener la ruta del curso para el usuario actual y cmid específico (si existe)
global $DB;
$path = $DB->get_record('learningstylesurvey_paths', [
    'courseid' => $courseid, 
    'userid' => $USER->id,
    'cmid' => $cmid
], '*', IGNORE_MISSING);
$pathid = $path ? $path->id : 0;
?>

<div style="margin: 2rem;">
    <ul style="list-style-type: none; padding-left: 0;">
        <li style="margin-bottom: 1rem;">
            <?php if ($pathid): ?>
                <span style="color: #666; font-style: italic;">✋ Crear Ruta de Aprendizaje (Ya existe una ruta para esta actividad)</span>
            <?php else: ?>
                <a href="createsteproute.php?courseid=<?php echo $courseid; ?>&cmid=<?php echo $cmid; ?>">📝 Crear Ruta de Aprendizaje</a>
            <?php endif; ?>
        </li>

        <li style="margin-bottom: 1rem;">
            <?php if ($pathid): ?>
                <a href="edit_learningpath.php?courseid=<?php echo $courseid; ?>&id=<?php echo $pathid; ?>&cmid=<?php echo $cmid; ?>">✏️ Editar Ruta de Aprendizaje</a>
            <?php else: ?>
                <span style="color: #666; font-style: italic;">✏️ Editar Ruta de Aprendizaje (No hay rutas creadas)</span>
            <?php endif; ?>
        </li>

        <li style="margin-bottom: 1rem;">
            <?php if ($pathid): ?>
                <a href="delete_learningpath.php?courseid=<?php echo $courseid; ?>&id=<?php echo $pathid; ?>&cmid=<?php echo $cmid; ?>">🗑️ Eliminar Ruta de Aprendizaje</a>
            <?php else: ?>
                <span style="color: #666; font-style: italic;">🗑️ Eliminar Ruta de Aprendizaje (No hay rutas creadas)</span>
            <?php endif; ?>
        </li>

        <?php /* 
        // Opción de modificar orden comentada - funcionalidad integrada en crear ruta
        <li style="margin-bottom: 1rem;">
            <?php if ($pathid): ?>
                <a href="organizar_ruta.php?courseid=<?php echo $courseid; ?>&pathid=<?php echo $pathid; ?>&cmid=<?php echo $cmid; ?>">🔄 Modificar Orden de la Ruta de Aprendizaje</a>
            <?php else: ?>
                <span style="color: #666; font-style: italic;">🔄 Modificar Orden de la Ruta (No hay rutas creadas)</span>
            <?php endif; ?>
        </li>
        */ ?>
    </ul>

    <!-- ✅ Botón de regreso con cmid dinámico -->
    <a href="<?php echo new moodle_url("/mod/learningstylesurvey/view.php", ["id" => $cmid]); ?>">
        <button style="background-color: #333; color: white; border: none; padding: 0.5rem 1rem; border-radius: 5px;">Regresar al Menú Anterior</button>
    </a>
</div>

<?php
echo $OUTPUT->footer();
?>
