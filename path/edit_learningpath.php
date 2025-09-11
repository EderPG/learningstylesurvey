<?php
require_once('../../../config.php');
require_login();

global $DB, $USER;

$courseid = required_param('courseid', PARAM_INT);
$cmid = required_param('cmid', PARAM_INT); // Obtener el Course Module ID para identificar la instancia
$context = context_course::instance($courseid);

// Verificar permisos de edición - solo profesores o usuarios con capacidad de editar
if (!has_capability('mod/learningstylesurvey:addinstance', $context) && 
    !has_capability('moodle/course:manageactivities', $context)) {
    throw new moodle_exception('nopermissiontoview', 'error');
}

$baseurl = new moodle_url('/mod/learningstylesurvey/path/edit_learningpath.php', ['courseid' => $courseid, 'cmid' => $cmid]);
$returnurl = new moodle_url('/mod/learningstylesurvey/path/learningpath.php', ['courseid' => $courseid, 'cmid' => $cmid]);

$PAGE->set_url($baseurl);
$PAGE->set_context($context);
$PAGE->set_title("Editar Ruta de Aprendizaje");
$PAGE->set_heading("Editar Ruta de Aprendizaje");

// Obtener rutas del usuario actual (el cmid se usa solo para navegación)
$rutas = $DB->get_records('learningstylesurvey_paths', array('courseid' => $courseid, 'userid' => $USER->id));

if (!isset($_POST['pathid']) && !isset($_GET['id'])) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading("Seleccionar Ruta de Aprendizaje");

    if (empty($rutas)) {
        echo '<div class="alert alert-warning">No tienes rutas creadas para editar.</div>';
        echo '<a href="' . $returnurl->out() . '" class="btn btn-secondary">Regresar</a>';
        echo $OUTPUT->footer();
        exit;
    }

    echo '<form method="post">';
    echo '<input type="hidden" name="courseid" value="' . $courseid . '">';
    echo '<div class="form-group">';
    echo '<label>Ruta a editar: </label>';
    echo '<select name="pathid" required class="form-control">';
    echo '<option value="">-- Selecciona una ruta --</option>';
    foreach ($rutas as $ruta) {
        echo '<option value="' . $ruta->id . '">' . format_string($ruta->name) . '</option>';
    }
    echo '</select>';
    echo '</div>';
    echo '<button type="submit" class="btn btn-primary">Editar</button> ';
    echo '<a href="' . $returnurl->out() . '" class="btn btn-secondary">Cancelar</a>';
    echo '</form>';

    echo $OUTPUT->footer();
    exit;
}

$pathid = isset($_POST['pathid']) ? (int) $_POST['pathid'] : (int) $_GET['id'];
$ruta = $DB->get_record('learningstylesurvey_paths', array('id' => $pathid, 'courseid' => $courseid, 'userid' => $USER->id), '*', MUST_EXIST);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {
    $nombre = required_param('nombre', PARAM_TEXT);
    $temas_hidden = optional_param('temas_hidden', '', PARAM_RAW);
    $temas_ids = [];
    if (!empty($temas_hidden)) {
        $temas_ids = array_filter(explode(',', $temas_hidden));
    }
    $evaluaciones = optional_param('evaluacion_hidden', '', PARAM_RAW);
    
    // Obtener campos de refuerzo y saltos
    $temas_refuerzo = optional_param('temas_refuerzo', '', PARAM_RAW);
    $saltos_aprueba = optional_param('saltos_aprueba', '', PARAM_RAW);
    $saltos_reprueba = optional_param('saltos_reprueba', '', PARAM_RAW);
    $orden_items = optional_param('orden_hidden', '', PARAM_RAW);

    // Debug temporal - mostrar valores recibidos
    $debug_mode = false; // Cambiar a false cuando funcione
    if ($debug_mode) {
        echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px; border: 1px solid #ccc;'>";
        echo "<h4>Debug - Valores recibidos:</h4>";
        echo "<p><strong>temas_hidden:</strong> '$temas_hidden'</p>";
        echo "<p><strong>temas_ids:</strong> " . print_r($temas_ids, true) . "</p>";
        echo "<p><strong>evaluaciones:</strong> '$evaluaciones'</p>";
        echo "<p><strong>orden_items:</strong> '$orden_items'</p>";
        echo "<p><strong>saltos_aprueba:</strong> '$saltos_aprueba'</p>";
        echo "<p><strong>saltos_reprueba:</strong> '$saltos_reprueba'</p>";
        echo "</div>";
    }

    if (empty($temas_ids) && empty($evaluaciones)) {
        redirect($baseurl . '&id=' . $pathid, "Debe existir al menos un recurso o una evaluación para los temas seleccionados.", 3);
        exit;
    }

    // Actualizar nombre de la ruta
    $ruta_update = new stdClass();
    $ruta_update->id = $pathid;
    $ruta_update->name = $nombre;
    $DB->update_record('learningstylesurvey_paths', $ruta_update);

    // SOLO eliminar temas y evaluaciones - MANTENER learningpath_steps
    $DB->delete_records('learningstylesurvey_path_temas', array('pathid' => $pathid));
    $DB->delete_records('learningstylesurvey_path_evaluations', array('pathid' => $pathid));
    
    // NO borrar learningpath_steps - solo actualizarlos

    $orden_array = !empty($orden_items) ? explode(',', $orden_items) : [];
    $step_number = 1;

    foreach ($orden_array as $item_id) {
        $item_id = trim($item_id);
        if (empty($item_id)) continue;

        if (strpos($item_id, 'tema_') === 0) {
            $tema_id = str_replace('tema_', '', $item_id);
            
            if (in_array($tema_id, $temas_ids)) {
                // Insertar tema en path_temas
                $path_tema = new stdClass();
                $path_tema->pathid = $pathid;
                $path_tema->temaid = $tema_id;
                $path_tema->orden = $step_number;
                $path_tema->isrefuerzo = strpos($temas_refuerzo, $tema_id) !== false ? 1 : 0;
                $DB->insert_record('learningstylesurvey_path_temas', $path_tema);

                // Obtener recursos del tema para learningpath_steps
                $tema_resources = $DB->get_records('learningstylesurvey_resources', [
                    'courseid' => $courseid,
                    'tema' => $tema_id,
                    'userid' => $USER->id
                ]);

                foreach ($tema_resources as $resource) {
                    // Verificar si ya existe un learningpath_step para este recurso
                    $existing_step = $DB->get_record('learningpath_steps', [
                        'pathid' => $pathid, 
                        'resourceid' => $resource->id, 
                        'istest' => 0
                    ]);

                    if ($existing_step) {
                        // Actualizar registro existente manteniendo saltos si los tiene
                        $step_update = new stdClass();
                        $step_update->id = $existing_step->id;
                        $step_update->stepnumber = $step_number;
                        // NO tocar los saltos existentes
                        $DB->update_record('learningpath_steps', $step_update);
                    } else {
                        // Crear nuevo registro solo si no existe
                        $step = new stdClass();
                        $step->pathid = $pathid;
                        $step->stepnumber = $step_number;
                        $step->resourceid = $resource->id;
                        $step->istest = 0;
                        $step->passredirect = 0;
                        $step->failredirect = 0;
                        $DB->insert_record('learningpath_steps', $step);
                    }
                }
                $step_number++;
            }
        } elseif (strpos($item_id, 'eval_') === 0) {
            $eval_id = str_replace('eval_', '', $item_id);
            
            if (strpos($evaluaciones, $eval_id) !== false) {
                // Insertar evaluación
                $path_eval = new stdClass();
                $path_eval->pathid = $pathid;
                $path_eval->quizid = $eval_id;
                $DB->insert_record('learningstylesurvey_path_evaluations', $path_eval);

                // Procesar saltos para esta evaluación
                $pass_redirect = 0;
                $fail_redirect = 0;
                
                if (!empty($saltos_aprueba)) {
                    $saltos_aprueba_array = explode(',', $saltos_aprueba);
                    foreach ($saltos_aprueba_array as $salto) {
                        if (strpos($salto, ':') !== false) {
                            list($eval_origen, $destino) = explode(':', $salto);
                            if ($eval_origen == $eval_id) {
                                // Buscar el primer recurso del tema destino
                                $primer_recurso = $DB->get_field_sql("
                                    SELECT r.id FROM {learningstylesurvey_resources} r
                                    WHERE r.tema = ? AND r.courseid = ? AND r.userid = ?
                                    ORDER BY r.id ASC LIMIT 1
                                ", [$destino, $courseid, $USER->id]);
                                
                                if ($primer_recurso) {
                                    $pass_redirect = $primer_recurso;
                                } else {
                                    // Si no hay recursos, usar el ID del tema como referencia
                                    $pass_redirect = $destino;
                                }
                            }
                        }
                    }
                }
                
                if (!empty($saltos_reprueba)) {
                    $saltos_reprueba_array = explode(',', $saltos_reprueba);
                    foreach ($saltos_reprueba_array as $salto) {
                        if (strpos($salto, ':') !== false) {
                            list($eval_origen, $destino) = explode(':', $salto);
                            if ($eval_origen == $eval_id) {
                                // Buscar el primer recurso del tema destino (para refuerzo)
                                $primer_recurso = $DB->get_field_sql("
                                    SELECT r.id FROM {learningstylesurvey_resources} r
                                    WHERE r.tema = ? AND r.courseid = ? AND r.userid = ?
                                    ORDER BY r.id ASC LIMIT 1
                                ", [$destino, $courseid, $USER->id]);
                                
                                if ($primer_recurso) {
                                    $fail_redirect = $primer_recurso;
                                } else {
                                    // Si no hay recursos, usar el ID del tema como referencia
                                    $fail_redirect = $destino;
                                }
                            }
                        }
                    }
                }

                // Verificar si ya existe un learningpath_step para esta evaluación
                $existing_step = $DB->get_record('learningpath_steps', [
                    'pathid' => $pathid, 
                    'resourceid' => $eval_id, 
                    'istest' => 1
                ]);

                if ($existing_step) {
                    // Actualizar registro existente
                    $step_update = new stdClass();
                    $step_update->id = $existing_step->id;
                    $step_update->stepnumber = $step_number;
                    
                    // Solo actualizar saltos si están configurados en el formulario
                    if ($pass_redirect > 0 || $fail_redirect > 0) {
                        $step_update->passredirect = $pass_redirect;
                        $step_update->failredirect = $fail_redirect;
                    }
                    // Si no hay saltos configurados, mantener los existentes
                    
                    $DB->update_record('learningpath_steps', $step_update);
                } else {
                    // Crear nuevo registro solo si no existe
                    $step = new stdClass();
                    $step->pathid = $pathid;
                    $step->stepnumber = $step_number;
                    $step->resourceid = $eval_id;
                    $step->istest = 1;
                    $step->passredirect = $pass_redirect;
                    $step->failredirect = $fail_redirect;
                    $DB->insert_record('learningpath_steps', $step);
                }
                $step_number++;
            }
        }
    }

    redirect($returnurl, "Ruta actualizada exitosamente.", 2);
}

// ✅ Cargar temas y evaluaciones - Filtrados por usuario (sin cmid en BD)
$temas = $DB->get_records('learningstylesurvey_temas', array('courseid' => $courseid, 'userid' => $USER->id));
$evaluaciones = $DB->get_records('learningstylesurvey_quizzes', array('courseid' => $courseid, 'userid' => $USER->id));

// Cargar datos existentes de la ruta
$path_temas = $DB->get_records('learningstylesurvey_path_temas', ['pathid' => $pathid], 'orden ASC');
$path_evaluaciones = $DB->get_records('learningstylesurvey_path_evaluations', ['pathid' => $pathid], 'id ASC'); // Sin orden, usar id
$existing_steps = $DB->get_records('learningpath_steps', ['pathid' => $pathid], 'stepnumber ASC');

echo $OUTPUT->header();
echo $OUTPUT->heading("Editar Ruta de Aprendizaje: " . format_string($ruta->name));

// Agregar jQuery UI para sortable
echo '<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>';
echo '<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/ui-lightness/jquery-ui.css">';
?>

<style>
    .route-builder {
        display: flex;
        gap: 30px;
        margin-top: 20px;
    }
    .left-panel {
        flex: 1;
        max-width: 400px;
    }
    .preview-panel {
        flex: 1;
        background: #f8f9fa;
        border: 2px dashed #dee2e6;
        border-radius: 12px;
        padding: 20px;
        min-height: 400px;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        font-weight: 600;
        color: #333;
        display: block;
        margin-bottom: 8px;
    }
    .form-control {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
    }
    .form-control:focus {
        border-color: #007bff;
        outline: none;
        box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
    }
    .btn {
        padding: 8px 16px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.3s ease;
    }
    .btn-primary {
        background: #007bff;
        color: white;
    }
    .btn-primary:hover {
        background: #0056b3;
        transform: translateY(-1px);
    }
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    .btn-danger {
        background: #dc3545;
        color: white;
    }
    .route-item {
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 10px;
        cursor: move;
        transition: all 0.3s ease;
        border-left: 5px solid #28a745;
    }
    .route-item:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transform: translateY(-1px);
    }
    
    /* Estilos para jQuery UI Sortable */
    .route-item.ui-sortable-placeholder {
        border: 2px dashed #007bff;
        background: #f8f9ff;
        height: 80px;
        visibility: visible !important;
        border-left: 5px dashed #007bff !important;
    }
    
    .route-item.ui-sortable-helper {
        box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        transform: rotate(2deg);
        opacity: 0.9;
    }
    
    #sortable-route {
        min-height: 50px;
    }
    .route-item.evaluacion {
        border-left: 5px solid #dc3545;
    }
    .route-item-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }
    .route-item-type {
        background: #6c757d;
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    .route-item.tema .route-item-type {
        background: #28a745;
    }
    .route-item.evaluacion .route-item-type {
        background: #dc3545;
    }
    .route-item-controls {
        display: flex;
        gap: 5px;
        margin-left: auto;
    }
    .btn-sm {
        padding: 4px 8px;
        font-size: 12px;
    }
    .empty-preview {
        text-align: center;
        color: #6c757d;
        padding: 60px 20px;
    }
    .empty-preview .icon {
        font-size: 48px;
        margin-bottom: 15px;
    }
    .sortable-placeholder {
        background: #e9ecef;
        border: 2px dashed #adb5bd;
        height: 60px;
        border-radius: 8px;
        margin-bottom: 10px;
    }
    
    .route-item.refuerzo {
        border-left: 4px solid #ff9800;
        background: linear-gradient(90deg, #fff8e1 0%, #ffffff 20%);
    }
    
    .route-item.refuerzo .route-item-type {
        background: #ff9800;
        color: white;
    }
    
    .btn-modern.btn-primary-modern {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-modern.btn-primary-modern:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,123,255,0.3);
    }
    
    .btn-modern.btn-secondary-modern {
        background: #6c757d;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-modern.btn-secondary-modern:hover {
        background: #545b62;
        transform: translateY(-1px);
    }
    
    .alert {
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    
    .alert-info {
        background-color: #d1ecf1;
        border: 1px solid #b6d4db;
        color: #0c5460;
    }
</style>

<div class="alert alert-info">
    <strong>📝 Editando ruta:</strong> Modifica los elementos existentes, agrega nuevos temas/evaluaciones y configura los saltos entre elementos.
</div>

<form method="post" id="route-form">
    <input type="hidden" name="pathid" value="<?php echo $pathid; ?>">
    <input type="hidden" name="courseid" value="<?php echo $courseid; ?>">
    <input type="hidden" name="guardar" value="1">
    <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
    
    <div class="form-group">
        <label>📝 Nombre de la ruta:</label>
        <input type="text" name="nombre" required class="form-control" value="<?php echo s($ruta->name); ?>" placeholder="Ingrese el nombre de la ruta de aprendizaje">
    </div>

    <div class="route-builder">
        <!-- Panel Izquierdo: Controles -->
        <div class="left-panel">
            <div class="form-group">
                <label>📚 Agregar Temas:</label>
                <select id="tema_select" class="form-control">
                    <option value="">-- Seleccione un tema --</option>
                    <?php foreach ($temas as $tema): ?>
                        <option value="<?php echo $tema->id; ?>" data-tema="<?php echo s($tema->tema); ?>">
                            <?php echo s($tema->tema); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="add_tema" class="btn btn-primary" style="margin-top: 10px;">➕ Agregar Tema</button>
            </div>

            <div class="form-group">
                <label>📋 Agregar Evaluaciones:</label>
                <select id="eval_select" class="form-control">
                    <option value="">-- Seleccione una evaluación --</option>
                    <?php foreach ($evaluaciones as $eval): ?>
                        <option value="<?php echo $eval->id; ?>" data-name="<?php echo s($eval->name); ?>">
                            <?php echo s($eval->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="add_eval" class="btn btn-primary" style="margin-top: 10px;">➕ Agregar Evaluación</button>
            </div>

            <div class="form-group">
                <button type="submit" class="btn-modern btn-primary-modern">💾 Guardar Cambios</button>
                <a href="<?php echo $returnurl->out(); ?>" class="btn-modern btn-secondary-modern">❌ Cancelar</a>
            </div>
        </div>

        <!-- Panel Derecho: Vista Previa -->
        <div class="preview-panel">
            <h4>🔄 Vista Previa de la Ruta</h4>
            <div id="route-preview">
                <!-- Los elementos se cargarán aquí -->
            </div>
        </div>
    </div>

    <!-- Campos ocultos para envío -->
    <input type="hidden" name="temas_hidden" id="temas_hidden">
    <input type="hidden" name="evaluacion_hidden" id="evaluacion_hidden">
    <input type="hidden" name="temas_refuerzo" id="temas_refuerzo">
    <input type="hidden" name="saltos_aprueba" id="saltos_aprueba">
    <input type="hidden" name="saltos_reprueba" id="saltos_reprueba">
    <input type="hidden" name="orden_hidden" id="orden_hidden">
</form>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.1/jquery-ui.min.js"></script>
<script>
$(document).ready(function() {
    let routeItems = [];
    let nextUniqueId = 1;

    // Datos de temas para búsqueda global - TODOS los temas del curso que tienen recursos
    const temasData = <?php 
        // Cargar TODOS los temas del curso que tienen recursos para poder resolver cualquier ID
        $sql = "SELECT t.id, t.tema 
                FROM {learningstylesurvey_temas} t
                WHERE t.courseid = ? AND t.userid = ?
                AND EXISTS (
                    SELECT 1 FROM {learningstylesurvey_resources} r 
                    WHERE r.tema = t.id AND r.courseid = t.courseid AND r.userid = t.userid
                )";
        $all_temas_with_resources = $DB->get_records_sql($sql, array($courseid, $USER->id));
        
        $temas_array = array();
        foreach ($all_temas_with_resources as $tema) {
            $temas_array[$tema->id] = $tema->tema;
        }
        echo json_encode($temas_array);
    ?>;

    // Función para obtener nombre de tema por ID (disponible globalmente)
    window.getTemaNameById = function(temaId) {
        console.log('Buscando tema ID:', temaId, 'en:', temasData);
        // Intentar buscar como string y como número
        let nombre = temasData[temaId] || temasData[String(temaId)] || temasData[parseInt(temaId)];
        if (!nombre) {
            console.warn('No se encontró tema con ID:', temaId);
            return `Tema ID: ${temaId}`;
        }
        return nombre;
    };

    // Función para obtener nombre de tema por ID de recurso
    window.getTemaNameByResourceId = function(resourceId) {
        // Los saltos apuntan a IDs de recursos, necesitamos el tema de ese recurso
        const recursosData = <?php 
            // Obtener mapeo de recurso_id -> tema_nombre
            $recursos_tema_map = $DB->get_records_sql("
                SELECT r.id as resource_id, t.tema as tema_name 
                FROM {learningstylesurvey_resources} r
                JOIN {learningstylesurvey_temas} t ON r.tema = t.id  
                WHERE r.courseid = ? AND r.userid = ?
            ", array($courseid, $USER->id));
            
            $map = array();
            foreach ($recursos_tema_map as $item) {
                $map[$item->resource_id] = $item->tema_name;
            }
            echo json_encode($map);
        ?>;
        
        console.log('Buscando tema para recurso ID:', resourceId, 'en:', recursosData);
        let temaName = recursosData[resourceId] || recursosData[String(resourceId)] || recursosData[parseInt(resourceId)];
        if (!temaName) {
            console.warn('No se encontró tema para recurso ID:', resourceId);
            return `Recurso ID: ${resourceId}`;
        }
        return temaName;
    };

    // Debug: mostrar datos cargados
    console.log('Datos de temas cargados:', temasData);
    console.log('PathID actual:', <?php echo $pathid; ?>);
    console.log('Datos de la ruta cargados desde BD:', <?php 
        echo json_encode([
            'path_temas' => array_values($path_temas),
            'path_evaluaciones' => array_values($path_evaluaciones),
            'all_items_ordered' => $all_items
        ]); 
    ?>);

    // Cargar datos existentes de la ruta
    <?php
    // Crear un array combinado de todos los elementos para mantener el orden correcto
    // Usaremos learningpath_steps como fuente de verdad para el orden
    $all_items = [];
    
    // Obtener el orden desde learningpath_steps
    $steps_sql = "SELECT DISTINCT stepnumber, resourceid, istest 
                  FROM {learningpath_steps} 
                  WHERE pathid = ? 
                  ORDER BY stepnumber ASC";
    $steps = $DB->get_records_sql($steps_sql, [$pathid]);
    
    foreach ($steps as $step) {
        if ($step->istest == 1) {
            // Es una evaluación
            $eval = $DB->get_record('learningstylesurvey_quizzes', ['id' => $step->resourceid]);
            if ($eval) {
                // Obtener saltos de esta evaluación
                $step_detail = $DB->get_record('learningpath_steps', [
                    'pathid' => $pathid, 
                    'resourceid' => $step->resourceid, 
                    'istest' => 1
                ]);
                
                $all_items[] = [
                    'type' => 'evaluacion',
                    'id' => $step->resourceid,
                    'name' => $eval->name,
                    'orden' => $step->stepnumber,
                    'passredirect' => $step_detail ? $step_detail->passredirect : 0,
                    'failredirect' => $step_detail ? $step_detail->failredirect : 0
                ];
            }
        } else {
            // Es un recurso - necesitamos encontrar el tema
            $resource = $DB->get_record('learningstylesurvey_resources', ['id' => $step->resourceid]);
            if ($resource) {
                $tema = $DB->get_record('learningstylesurvey_temas', ['id' => $resource->tema]);
                if ($tema) {
                    // Verificar si este tema ya fue agregado (evitar duplicados)
                    $tema_ya_agregado = false;
                    foreach ($all_items as $existing_item) {
                        if ($existing_item['type'] === 'tema' && $existing_item['id'] == $resource->tema) {
                            $tema_ya_agregado = true;
                            break;
                        }
                    }
                    
                    if (!$tema_ya_agregado) {
                        // Verificar si es tema de refuerzo
                        $path_tema = $DB->get_record('learningstylesurvey_path_temas', 
                            ['pathid' => $pathid, 'temaid' => $resource->tema]);
                        
                        $all_items[] = [
                            'type' => 'tema',
                            'id' => $resource->tema,
                            'name' => $tema->tema,
                            'orden' => $step->stepnumber,
                            'es_refuerzo' => $path_tema ? $path_tema->isrefuerzo : 0
                        ];
                    }
                }
            }
        }
    }
    
    // Los elementos ya vienen ordenados desde la consulta SQL
    // Generar JavaScript para cada elemento en el orden correcto
    foreach ($all_items as $item) {
        if ($item['type'] === 'tema') {
            echo "routeItems.push({
                uniqueId: 'tema_{$item['id']}',
                type: 'tema',
                id: {$item['id']},
                name: " . json_encode($item['name']) . ",
                orden: {$item['orden']},
                es_refuerzo: " . ($item['es_refuerzo'] ? 'true' : 'false') . ",
                passredirect: null,
                failredirect: null
            });\n";
        } else {
            echo "routeItems.push({
                uniqueId: 'eval_{$item['id']}',
                type: 'evaluacion',
                id: {$item['id']},
                name: " . json_encode($item['name']) . ",
                orden: {$item['orden']},
                es_refuerzo: false,
                passredirect: " . (isset($item['passredirect']) ? intval($item['passredirect']) : 0) . ",
                failredirect: " . (isset($item['failredirect']) ? intval($item['failredirect']) : 0) . "
            });\n";
        }
    }
    ?>

    // Ajustar nextUniqueId
    if (routeItems.length > 0) {
        nextUniqueId = Math.max(...routeItems.map(item => {
            if (item.uniqueId.includes('_')) {
                return parseInt(item.uniqueId.split('_')[1]) + 1;
            }
            return 1;
        }));
    }

    function updatePreview() {
        const preview = $('#route-preview');
        
        if (routeItems.length === 0) {
            preview.html(`
                <div class="empty-preview">
                    <div class="icon">📋</div>
                    <p>Arrastre elementos aquí para crear la ruta</p>
                    <small>La ruta aparecerá vacía hasta que agregue temas o evaluaciones</small>
                </div>
            `);
            return;
        }

        let html = '<div id="sortable-route">';
        routeItems.forEach((item, index) => {
            const isRefuerzo = item.es_refuerzo;
            const refuerzoClass = isRefuerzo ? ' refuerzo' : '';
            const refuerzoText = isRefuerzo ? ' (Refuerzo)' : '';
            
            html += `
                <div class="route-item ${item.type}${refuerzoClass}" data-unique-id="${item.uniqueId}">
                    <div class="route-item-header">
                        <span class="route-item-type">${item.type.toUpperCase()}${refuerzoText}</span>
                        <div class="route-item-controls">`;
            
            if (item.type === 'tema') {
                const toggleText = isRefuerzo ? 'Normal' : 'Refuerzo';
                html += `<button type="button" class="btn btn-sm btn-secondary toggle-refuerzo" data-unique-id="${item.uniqueId}">${toggleText}</button>`;
            }
            
            if (item.type === 'evaluacion') {
                html += `<button type="button" class="btn btn-sm btn-secondary config-saltos" data-unique-id="${item.uniqueId}">⚙️ Saltos</button>`;
            }
            
            html += `
                            <button type="button" class="btn btn-sm btn-danger remove-item" data-unique-id="${item.uniqueId}">🗑️</button>
                        </div>
                    </div>
                    <div><strong>${item.name}</strong></div>`;
            
            // Mostrar saltos configurados
            if (item.type === 'evaluacion') {
                if (item.passredirect) {
                    // Los saltos apuntan a IDs de recursos, obtener el nombre del tema
                    const passText = window.getTemaNameByResourceId(item.passredirect);
                    html += `<small>✅ Si aprueba → ${passText}</small><br>`;
                }
                if (item.failredirect) {
                    // Los saltos apuntan a IDs de recursos, obtener el nombre del tema  
                    const failText = window.getTemaNameByResourceId(item.failredirect) + ' (Refuerzo)';
                    html += `<small>❌ Si reprueba → ${failText}</small>`;
                }
            }
            
            html += '</div>';
        });
        html += '</div>';
        
        preview.html(html);
        
        // Hacer sortable la lista para reordenar
        $("#sortable-route").sortable({
            update: function(event, ui) {
                // Actualizar el orden de routeItems basado en el nuevo orden visual
                const sortedUniqueIds = $("#sortable-route .route-item").map(function() {
                    return $(this).data('unique-id');
                }).get();
                
                // Reordenar el array routeItems según el nuevo orden
                const newOrderItems = [];
                sortedUniqueIds.forEach(uniqueId => {
                    const item = routeItems.find(item => item.uniqueId === uniqueId);
                    if (item) {
                        newOrderItems.push(item);
                    }
                });
                
                routeItems = newOrderItems;
                updateHiddenFields();
            }
        }).disableSelection();
        
        // Hacer sortable
        $("#sortable-route").sortable({
            placeholder: "sortable-placeholder",
            update: function(event, ui) {
                // Actualizar el orden de routeItems basado en el DOM
                const sortedUniqueIds = $("#sortable-route .route-item").map(function() {
                    return $(this).data('unique-id');
                }).get();
                
                const newOrderItems = [];
                sortedUniqueIds.forEach(uniqueId => {
                    const item = routeItems.find(item => item.uniqueId === uniqueId);
                    if (item) {
                        newOrderItems.push(item);
                    }
                });
                
                routeItems = newOrderItems;
                updateHiddenFields();
            }
        });
    }

    function updateOrder() {
        const sortedIds = $("#sortable-route .route-item").map(function() {
            return $(this).data('unique-id');
        }).get();
        
        $('#orden_hidden').val(sortedIds.join(','));
    }

    function updateHiddenFields() {
        const temas = routeItems.filter(item => item.type === 'tema').map(item => item.id);
        const evaluaciones = routeItems.filter(item => item.type === 'evaluacion').map(item => item.id);
        const temasRefuerzo = routeItems.filter(item => item.type === 'tema' && item.es_refuerzo).map(item => item.id);
        
        const saltosAprueba = routeItems
            .filter(item => item.type === 'evaluacion' && item.passredirect)
            .map(item => `${item.id}:${item.passredirect}`)
            .join(',');
        
        const saltosReprueba = routeItems
            .filter(item => item.type === 'evaluacion' && item.failredirect)
            .map(item => `${item.id}:${item.failredirect}`)
            .join(',');
        
        // Generar orden basado en uniqueId de los elementos
        const orden = routeItems.map(item => item.uniqueId).join(',');
        
        $('#temas_hidden').val(temas.join(','));
        $('#evaluacion_hidden').val(evaluaciones.join(','));
        $('#temas_refuerzo').val(temasRefuerzo.join(','));
        $('#saltos_aprueba').val(saltosAprueba);
        $('#saltos_reprueba').val(saltosReprueba);
        $('#orden_hidden').val(orden);
    }

    // Agregar tema
    $('#add_tema').click(function() {
        const select = $('#tema_select');
        const temaId = select.val();
        const temaNombre = select.find('option:selected').data('tema');
        
        if (!temaId) {
            alert('Por favor seleccione un tema');
            return;
        }
        
        // Verificar si ya existe
        if (routeItems.some(item => item.type === 'tema' && item.id == temaId)) {
            alert('Este tema ya está agregado');
            return;
        }
        
        routeItems.push({
            uniqueId: 'tema_' + temaId,
            type: 'tema',
            id: parseInt(temaId),
            name: temaNombre,
            es_refuerzo: false,
            passredirect: null,
            failredirect: null
        });
        
        select.val('');
        updatePreview();
        updateHiddenFields();
    });

    // Agregar evaluación
    $('#add_eval').click(function() {
        const select = $('#eval_select');
        const evalId = select.val();
        const evalNombre = select.find('option:selected').data('name');
        
        if (!evalId) {
            alert('Por favor seleccione una evaluación');
            return;
        }
        
        // Verificar si ya existe
        if (routeItems.some(item => item.type === 'evaluacion' && item.id == evalId)) {
            alert('Esta evaluación ya está agregada');
            return;
        }
        
        routeItems.push({
            uniqueId: 'eval_' + evalId,
            type: 'evaluacion',
            id: parseInt(evalId),
            name: evalNombre,
            es_refuerzo: false,
            passredirect: null,
            failredirect: null
        });
        
        select.val('');
        updatePreview();
        updateHiddenFields();
    });

    // Eliminar elemento
    $(document).on('click', '.remove-item', function() {
        const uniqueId = $(this).data('unique-id');
        if (confirm('¿Está seguro de eliminar este elemento?')) {
            routeItems = routeItems.filter(item => item.uniqueId !== uniqueId);
            updatePreview();
            updateHiddenFields();
        }
    });

    // Toggle refuerzo
    $(document).on('click', '.toggle-refuerzo', function() {
        const uniqueId = $(this).data('unique-id');
        const item = routeItems.find(item => item.uniqueId === uniqueId);
        if (item) {
            item.es_refuerzo = !item.es_refuerzo;
            updatePreview();
            updateHiddenFields();
        }
    });

    // Configurar saltos
    $(document).on('click', '.config-saltos', function() {
        const uniqueId = $(this).data('unique-id');
        const item = routeItems.find(item => item.uniqueId === uniqueId);
        if (!item) return;

        const temasDisponibles = routeItems.filter(r => r.type === 'tema');
        
        if (temasDisponibles.length === 0) {
            alert('Debe agregar temas antes de configurar saltos');
            return;
        }

        let optionsPass = '<option value="">-- Sin salto --</option>';
        let optionsFail = '<option value="">-- Sin salto --</option>';
        
        temasDisponibles.forEach(r => {
            // Usar el ID real del tema, no el uniqueId
            optionsPass += `<option value="${r.id}" ${item.passredirect == r.id ? 'selected' : ''}>${r.name}</option>`;
            // Solo mostrar "(Refuerzo)" si el tema realmente es de refuerzo
            const refuerzoText = r.es_refuerzo ? ' (Refuerzo)' : '';
            optionsFail += `<option value="${r.id}" ${item.failredirect == r.id ? 'selected' : ''}>${r.name}${refuerzoText}</option>`;
        });

        const html = `
            <div class="config-popup" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1000; width: 400px;">
                <h4>⚙️ Configurar Saltos para: ${item.name}</h4>
                <div style="margin-bottom: 15px;">
                    <label>Si aprueba, saltar a:</label>
                    <select id="salto-aprueba" class="form-control">${optionsPass}</select>
                </div>
                <div style="margin-bottom: 15px;">
                    <label>Si reprueba, saltar a:</label>
                    <select id="salto-reprueba" class="form-control">${optionsFail}</select>
                </div>
                <button type="button" id="save-saltos" class="btn btn-primary">💾 Guardar</button>
                <button type="button" id="cancel-saltos" class="btn btn-secondary">❌ Cancelar</button>
            </div>
            <div id="overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999;"></div>
        `;
        
        $('body').append(html);
        
        $('#save-saltos').click(function() {
            const passValue = $('#salto-aprueba').val();
            const failValue = $('#salto-reprueba').val();
            
            // Convertir a números o null
            item.passredirect = passValue ? parseInt(passValue) : null;
            item.failredirect = failValue ? parseInt(failValue) : null;
            
            $('.config-popup, #overlay').remove();
            updatePreview();
            updateHiddenFields();
        });
        
        $('#cancel-saltos, #overlay').click(function() {
            $('.config-popup, #overlay').remove();
        });
    });

    // Validación antes de enviar
    $('#route-form').submit(function(e) {
        if (routeItems.length === 0) {
            e.preventDefault();
            alert('Debe agregar al menos un tema o evaluación a la ruta');
            return false;
        }
        updateHiddenFields();
        return true;
    });

    // Cargar vista inicial
    updatePreview();
    updateHiddenFields();
});
</script>

<?php
echo $OUTPUT->footer();
?>
