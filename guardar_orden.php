<?php
require_once('../../config.php');
global $DB;
require_login();

$data = json_decode(file_get_contents('php://input'), true);

foreach ($data as $item) {
    if ($item['tipo'] === 'recurso') {
        // Actualiza el orden en la tabla de recursos
        $DB->set_field('learningstylesurvey_path_files', 'steporder', $item['orden'], [
            'pathid' => $item['pathid'],
            'filename' => $DB->get_field('learningstylesurvey_inforoute', 'filename', ['id' => $item['id']])
        ]);
    } elseif ($item['tipo'] === 'examen') {
        // Actualiza el orden en la tabla de evaluaciones
        $DB->set_field('learningstylesurvey_path_evaluations', 'steporder', $item['orden'], [
            'pathid' => $item['pathid'],
            'quizid' => $item['id']
        ]);
    }
}

echo "ok";
?>
