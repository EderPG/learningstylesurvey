
<?php
require_once('../../config.php');
require_login();

$nombre = required_param('nombre', PARAM_TEXT);
$archivo = required_param('archivo', PARAM_TEXT);
$instrucciones = optional_param('instrucciones', '', PARAM_TEXT);
$courseid = required_param('courseid', PARAM_INT);
$userid = $USER->id;
$time = time();

global $DB;
$DB->insert_record('learningstylesurvey_inforoute', (object)[
    'filename' => $archivo,
    'descripcion' => $instrucciones,
    'userid' => $userid,
    'timecreated' => $time,
    'courseid' => $courseid,
    'steporder' => 0
]);

redirect(new moodle_url('/mod/learningstylesurvey/ver_ruta_informativa.php', ['courseid' => $courseid]), 'Paso agregado correctamente', 2);
?>
