<?php
require_once("../../config.php");
global $DB, $USER;

require_login();

$stepid = required_param('stepid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);

// ✅ Verificar paso actual
$current = $DB->get_record('learningpath_steps', ['id' => $stepid]);
if (!$current) {
    // Si el paso no existe, redirigir al inicio de la ruta
    $firststep = $DB->get_record_sql("
        SELECT * FROM {learningpath_steps}
        WHERE pathid = ? ORDER BY stepnumber ASC LIMIT 1", [$current->pathid ?? 0]);
    if ($firststep) {
        redirect(new moodle_url('/mod/learningstylesurvey/vista_estudiante.php', [
            'courseid' => $courseid,
            'pathid' => $firststep->pathid
        ]));
    } else {
        throw new moodle_exception('No hay pasos definidos para esta ruta.');
    }
}

// ✅ Obtener progreso del usuario
$progress = $DB->get_record('learningstylesurvey_user_progress', [
    'userid' => $USER->id,
    'pathid' => $current->pathid
]);

if (!$progress) {
    // Si no existe progreso, crearlo apuntando al primer paso
    $firststep = $DB->get_record_sql("
        SELECT * FROM {learningpath_steps}
        WHERE pathid = ? ORDER BY stepnumber ASC LIMIT 1", [$current->pathid]);
    $progress = (object)[
        'userid' => $USER->id,
        'pathid' => $current->pathid,
        'current_stepid' => $firststep->id,
        'status' => 'inprogress',
        'timemodified' => time()
    ];
    $progress->id = $DB->insert_record('learningstylesurvey_user_progress', $progress);
}

// ✅ Buscar el siguiente paso en orden
$nextstep = $DB->get_record_sql("
    SELECT * FROM {learningpath_steps}
    WHERE pathid = ? AND stepnumber > ?
    ORDER BY stepnumber ASC LIMIT 1",
    [$current->pathid, $current->stepnumber]
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
    'pathid' => $current->pathid
]));
