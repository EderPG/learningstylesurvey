<?php
require_once("../../../config.php");
global $DB, $USER;

require_login();

$stepid = required_param('stepid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$pathid = optional_param('pathid', 0, PARAM_INT);
$cmid = optional_param('cmid', 0, PARAM_INT); // Para aislamiento por instancia

// ✅ Verificar paso actual
$current = $DB->get_record('learningpath_steps', ['id' => $stepid]);
if (!$current) {
    // Si el paso no existe, redirigir al inicio de la ruta
    if ($pathid) {
        $firststep = $DB->get_record_sql("
            SELECT * FROM {learningpath_steps}
            WHERE pathid = ? ORDER BY stepnumber ASC LIMIT 1", [$pathid]);
    } else {
        throw new moodle_exception('Paso no encontrado y pathid no proporcionado.');
    }
    
    if ($firststep) {
        redirect(new moodle_url('/mod/learningstylesurvey/path/vista_estudiante.php', [
            'courseid' => $courseid,
            'pathid' => $firststep->pathid,
            'cmid' => $cmid
        ]));
    } else {
        throw new moodle_exception('No hay pasos definidos para esta ruta.');
    }
}

// Usar el pathid del paso actual si no se proporcionó
if (!$pathid) {
    $pathid = $current->pathid;
}

// ✅ Obtener progreso del usuario
$progress = $DB->get_record('learningstylesurvey_user_progress', [
    'userid' => $USER->id,
    'pathid' => $pathid
]);

if (!$progress) {
    // Si no existe progreso, crearlo apuntando al primer paso
    $firststep = $DB->get_record_sql("
        SELECT * FROM {learningpath_steps}
        WHERE pathid = ? ORDER BY stepnumber ASC LIMIT 1", [$pathid]);
    $progress = (object)[
        'userid' => $USER->id,
        'pathid' => $pathid,
        'current_stepid' => $firststep->id,
        'status' => 'inprogress',
        'timemodified' => time()
    ];
    $progress->id = $DB->insert_record('learningstylesurvey_user_progress', $progress);
}

// ✅ Obtener el estilo del usuario para filtrar recursos apropiados
$userstyle = $DB->get_record_sql("
    SELECT style FROM {learningstylesurvey_userstyles}
    WHERE userid = ? ORDER BY timecreated DESC LIMIT 1
", [$USER->id]);

$style = $userstyle ? $userstyle->style : 'visual'; // Fallback por defecto

// ✅ Buscar el siguiente paso en orden que coincida con el estilo del usuario y NO sea tema de refuerzo
$nextstep = $DB->get_record_sql("
    SELECT s.* FROM {learningpath_steps} s
    LEFT JOIN {learningstylesurvey_resources} r ON s.resourceid = r.id AND s.istest = 0
    LEFT JOIN {learningstylesurvey_path_temas} pt ON pt.temaid = r.tema AND pt.pathid = s.pathid
    WHERE s.pathid = ? AND s.stepnumber > ? 
    AND (
        (s.istest = 1) OR 
        (s.istest = 0 AND r.style = ? AND r.userid = ? AND (pt.isrefuerzo = 0 OR pt.isrefuerzo IS NULL))
    )
    ORDER BY s.stepnumber ASC LIMIT 1",
    [$pathid, $current->stepnumber, $style, $USER->id]
);

// ✅ Actualizar progreso
if ($nextstep) {
    $progress->current_stepid = $nextstep->id;
    $progress->timemodified = time();
    $DB->update_record('learningstylesurvey_user_progress', $progress);
    
    // Redirigir al siguiente paso específico si es un recurso
    if (!$nextstep->istest) {
        redirect(new moodle_url('/mod/learningstylesurvey/path/vista_estudiante.php', [
            'courseid' => $courseid,
            'pathid' => $pathid,
            'stepid' => $nextstep->id,
            'cmid' => $cmid
        ]));
    } else {
        // Si es un examen, redirigir sin stepid para que maneje la lógica de examen
        redirect(new moodle_url('/mod/learningstylesurvey/path/vista_estudiante.php', [
            'courseid' => $courseid,
            'pathid' => $pathid,
            'cmid' => $cmid
        ]));
    }
} else {
    // Si no hay más pasos, marcar como completado
    $progress->status = 'completed';
    $progress->timemodified = time();
    $DB->update_record('learningstylesurvey_user_progress', $progress);
    
    // Redirigir mostrando mensaje de finalización
    redirect(new moodle_url('/mod/learningstylesurvey/path/vista_estudiante.php', [
        'courseid' => $courseid,
        'pathid' => $pathid,
        'completed' => 1,
        'cmid' => $cmid
    ]));
}
