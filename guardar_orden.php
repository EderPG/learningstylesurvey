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
        continue; // Saltar si falta información
    }

    $id = intval($item['id']);
    $orden = intval($item['orden']);
    $passredirect = isset($item['passredirect']) && $item['passredirect'] !== '' ? intval($item['passredirect']) : 0;
    $failredirect = isset($item['failredirect']) && $item['failredirect'] !== '' ? intval($item['failredirect']) : 0;

    // Verificar que el paso exista
    if ($DB->record_exists('learningpath_steps', ['id' => $id])) {
        $update = new stdClass();
        $update->id = $id;
        $update->stepnumber = $orden;
        $update->passredirect = $passredirect;
        $update->failredirect = $failredirect;

        $DB->update_record('learningpath_steps', $update);
    }
}

echo json_encode(['status' => 'success', 'message' => 'Cambios guardados correctamente.']);
?>
