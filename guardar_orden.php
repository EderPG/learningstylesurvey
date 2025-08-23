<?php
require_once('../../config.php');
require_login();

global $DB;

// Leer datos enviados por fetch()
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !is_array($data)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Datos inválidos.']);
    exit;
}

foreach ($data as $item) {
    // Validar campos requeridos
    if (!isset($item['id'], $item['orden'])) {
        continue;
    }

    $id = intval($item['id']);
    $orden = intval($item['orden']);
    $passredirect = isset($item['passredirect']) && $item['passredirect'] !== '' ? intval($item['passredirect']) : 0;
    $failredirect = isset($item['failredirect']) && $item['failredirect'] !== '' ? intval($item['failredirect']) : 0;
    $isrefuerzo = isset($item['isrefuerzo']) ? (int)$item['isrefuerzo'] : 0;
    $tipo = isset($item['tipo']) ? $item['tipo'] : '';

    if ($tipo === 'tema') {
        // Usar pathid recibido desde el frontend
        $pathid = isset($item['pathid']) ? intval($item['pathid']) : 0;
        if (!$pathid) {
            $pathtema_tmp = $DB->get_record('learningstylesurvey_path_temas', ['temaid' => $id]);
            $pathid = $pathtema_tmp ? $pathtema_tmp->pathid : 0;
        }

        // Actualizar estado de refuerzo y orden en la tabla de temas de la ruta
        $pathtema = $DB->get_record('learningstylesurvey_path_temas', ['pathid' => $pathid, 'temaid' => $id]);
        if ($pathtema) {
            $pathtema->isrefuerzo = $isrefuerzo;
            $pathtema->orden = $orden;
            $DB->update_record('learningstylesurvey_path_temas', $pathtema);
        }

        // Verificar si existe paso en learningpath_steps para este tema (recurso)
        $recursos = $DB->get_records('learningstylesurvey_resources', ['tema' => $id]);
        foreach ($recursos as $recurso) {
            $step = $DB->get_record('learningpath_steps', ['resourceid' => $recurso->id, 'pathid' => $pathid]);
            if ($step) {
                // Actualizar orden
                $step->stepnumber = $orden;
                $DB->update_record('learningpath_steps', $step);
            } else {
                // Crear paso si no existe
                $newstep = new stdClass();
                $newstep->pathid = $pathid;
                $newstep->stepnumber = $orden;
                $newstep->resourceid = $recurso->id;
                $newstep->istest = 0;
                $newstep->passredirect = 0;
                $newstep->failredirect = 0;
                $DB->insert_record('learningpath_steps', $newstep);
            }
        }
    }
    if ($tipo === 'examen') {
        // Actualizar saltos en el paso del examen
        $step = $DB->get_record('learningpath_steps', ['id' => $id]);
        if ($step) {
            $step->stepnumber = $orden;
            $step->passredirect = $passredirect;
            $step->failredirect = $failredirect;
            $DB->update_record('learningpath_steps', $step);
        } else {
            // Si no existe, crear el paso (buscando el quiz)
            $quiz = $DB->get_record('learningstylesurvey_quizzes', ['id' => $id]);
            if ($quiz) {
                // Necesitamos el pathid, lo buscamos por el primer tema de la ruta
                $pathtema = $DB->get_records('learningstylesurvey_path_temas', null, '', 'pathid');
                $pathid = $pathtema ? reset($pathtema)->pathid : 0;
                $newstep = new stdClass();
                $newstep->pathid = $pathid;
                $newstep->stepnumber = $orden;
                $newstep->resourceid = $quiz->id;
                $newstep->istest = 1;
                $newstep->passredirect = $passredirect;
                $newstep->failredirect = $failredirect;
                $DB->insert_record('learningpath_steps', $newstep);
            }
        }
    }
}

echo json_encode(['status' => 'success', 'message' => 'Cambios guardados correctamente.']);
?>
