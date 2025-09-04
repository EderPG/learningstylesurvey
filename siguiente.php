<?php
require_once("../../config.php");
global $DB, $USER;

require_login();

$stepid = required_param('stepid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$pathid = optional_param('pathid', 0, PARAM_INT);

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
        redirect(new moodle_url('/mod/learningstylesurvey/vista_estudiante.php', [
            'courseid' => $courseid,
            'pathid' => $firststep->pathid
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

// ✅ Buscar el siguiente paso en orden que NO sea tema de refuerzo
$nextstep = $DB->get_record_sql("
    SELECT s.* FROM {learningpath_steps} s
    LEFT JOIN {learningstylesurvey_resources} r ON s.resourceid = r.id AND s.istest = 0
    LEFT JOIN {learningstylesurvey_path_temas} pt ON pt.temaid = r.tema AND pt.pathid = s.pathid
    WHERE s.pathid = ? AND s.stepnumber > ? 
    AND (s.istest = 1 OR pt.isrefuerzo = 0 OR pt.isrefuerzo IS NULL)
    ORDER BY s.stepnumber ASC LIMIT 1",
    [$pathid, $current->stepnumber]
);

// ✅ Actualizar progreso
if ($nextstep) {
    $progress->current_stepid = $nextstep->id;
    $progress->timemodified = time();
    $DB->update_record('learningstylesurvey_user_progress', $progress);
} else {
    // Si no hay más pasos, marcar como completado
    $progress->status = 'completed';
    $progress->timemodified = time();
    $DB->update_record('learningstylesurvey_user_progress', $progress);
}

// ✅ Redirigir de vuelta a la vista de la ruta
redirect(new moodle_url('/mod/learningstylesurvey/vista_estudiante.php', [
    'courseid' => $courseid,
    'pathid' => $pathid
]));
