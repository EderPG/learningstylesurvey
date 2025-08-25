<?php
require_once("../../config.php");
global $DB, $USER;

require_login();

$courseid = required_param('courseid', PARAM_INT);
$pathid = required_param('pathid', PARAM_INT);
$tema_actual = required_param('tema_actual', PARAM_INT);

// Después de completar un tema por salto adaptativo, 
// continuar con el siguiente paso en la ruta normal

// Buscar el siguiente paso que no sea del tema actual
$nextstep = $DB->get_record_sql("
    SELECT s.* FROM {learningpath_steps} s
    LEFT JOIN {learningstylesurvey_resources} r ON s.resourceid = r.id AND s.istest = 0
    WHERE s.pathid = ? 
    AND (s.istest = 1 OR r.tema != ? OR r.tema IS NULL)
    ORDER BY s.stepnumber ASC 
    LIMIT 1
", [$pathid, $tema_actual]);

if ($nextstep) {
    // Actualizar progreso del usuario
    $progress = $DB->get_record('learningstylesurvey_user_progress', [
        'userid' => $USER->id,
        'pathid' => $pathid
    ]);

    if ($progress) {
        $progress->current_stepid = $nextstep->id;
        $progress->timemodified = time();
        $DB->update_record('learningstylesurvey_user_progress', $progress);
    } else {
        // Crear progreso si no existe
        $progress = (object)[
            'userid' => $USER->id,
            'pathid' => $pathid,
            'current_stepid' => $nextstep->id,
            'status' => 'inprogress',
            'timemodified' => time()
        ];
        $DB->insert_record('learningstylesurvey_user_progress', $progress);
    }

    // Redirigir al siguiente paso
    redirect(new moodle_url('/mod/learningstylesurvey/vista_estudiante.php', [
        'courseid' => $courseid,
        'pathid' => $pathid,
        'stepid' => $nextstep->id
    ]));
} else {
    // Si no hay más pasos, volver a la vista general
    redirect(new moodle_url('/mod/learningstylesurvey/vista_estudiante.php', [
        'courseid' => $courseid,
        'pathid' => $pathid
    ]));
}
?>
