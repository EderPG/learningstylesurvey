<?php
require_once('../../config.php');
require_login();

$courseid = required_param('courseid', PARAM_INT);
$nombre = required_param('nombre', PARAM_TEXT);
$indicaciones = optional_param('indicaciones', '', PARAM_TEXT);

$context = context_course::instance($courseid);
$PAGE->set_context($context);
$PAGE->set_url('/mod/learningstylesurvey/guardar_ruta.php');
$PAGE->set_title("Guardar Ruta");
$PAGE->set_heading("Guardar Ruta");

global $DB, $USER;

// Subir archivo si existe
$filename = '';
if (!empty($_FILES['archivo']['name'])) {
    $filename = $_FILES['archivo']['name'];
    $filepath = $CFG->dataroot . '/learningstylesurvey_files';
    if (!file_exists($filepath)) {
        mkdir($filepath, 0777, true);
    }
    move_uploaded_file($_FILES['archivo']['tmp_name'], $filepath . '/' . $filename);
}

// Guardar en la base de datos
$record = new stdClass();
$record->courseid = $courseid;
$record->userid = $USER->id;
$record->filename = $filename;
$record->indicaciones = $indicaciones;
$record->timecreated = time();

$DB->insert_record('learningstylesurvey_inforoute', $record);

redirect(new moodle_url('/mod/learningstylesurvey/learningpath.php', ['courseid' => $courseid]));
?>
