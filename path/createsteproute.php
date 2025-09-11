<?php
require_once('../../../config.php');
require_login();

global $DB, $USER;

$courseid = required_param('courseid', PARAM_INT);
$cmid = optional_param('cmid', 0, PARAM_INT);

// Si no se proporciona cmid, obtenerlo del context del módulo actual
if (!$cmid) {
    $modinfo = get_fast_modinfo($courseid);
    foreach ($modinfo->get_cms() as $cm) {
        if ($cm->modname === 'learningstylesurvey') {
            $cmid = $cm->id;
            break;
        }
    }
}

$context = context_course::instance($courseid);

// Verificar permisos de creación - solo profesores o usuarios con capacidad de editar
if (!has_capability('mod/learningstylesurvey:addinstance', $context) && 
    !has_capability('moodle/course:manageactivities', $context)) {
    throw new moodle_exception('nopermissiontoview', 'error');
}

$baseurl = new moodle_url('/mod/learningstylesurvey/path/createsteproute.php', ['courseid' => $courseid, 'cmid' => $cmid]);
$returnurl = new moodle_url('/mod/learningstylesurvey/path/learningpath.php', ['courseid' => $courseid, 'cmid' => $cmid]);

$PAGE->set_url($baseurl);
$PAGE->set_context($context);
$PAGE->set_title("Ruta de Aprendizaje");
$PAGE->set_heading("Ruta de Aprendizaje");

// Verificar si ya existe una ruta para este usuario, curso y cmid específico
$existing_path = $DB->get_record('learningstylesurvey_paths', [
    'courseid' => $courseid, 
    'userid' => $USER->id,
    'cmid' => $cmid
]);
if ($existing_path) {
    redirect($returnurl, "Ya tienes una ruta creada para esta instancia del plugin. Solo se permite una ruta por actividad.", 3);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    
    // Procesar temas de refuerzo
    $refuerzo_ids = [];
    if (!empty($temas_refuerzo)) {
        $refuerzo_ids = array_filter(explode(',', $temas_refuerzo));
    }
    
    // Procesar saltos
    $saltos_pass = [];
    $saltos_fail = [];
    
    if (!empty($saltos_aprueba)) {
        foreach (explode('|', $saltos_aprueba) as $salto) {
            if (!empty($salto)) {
                $parts = explode(':', $salto);
                if (count($parts) == 3) {
                    $saltos_pass[$parts[0]] = ['type' => $parts[1], 'id' => $parts[2]];
                }
            }
        }
    }
    
    if (!empty($saltos_reprueba)) {
        foreach (explode('|', $saltos_reprueba) as $salto) {
            if (!empty($salto)) {
                $parts = explode(':', $salto);
                if (count($parts) == 3) {
                    $saltos_fail[$parts[0]] = ['type' => $parts[1], 'id' => $parts[2]];
                }
            }
        }
    }

    $archivos = [];
    foreach ($temas_ids as $tema_id) {
        $archivos_tema = $DB->get_records('learningstylesurvey_resources', [
            'courseid' => $courseid,
            'tema' => $tema_id
        ]);
        $archivos = array_merge($archivos, $archivos_tema);
    }

    // Convertir evaluaciones seleccionadas (del campo oculto) a array
    $evaluaciones_array = [];
    if (!empty($evaluaciones)) {
        $evaluaciones_array = array_filter(explode(',', $evaluaciones));
    }

    if (empty($archivos) && empty($evaluaciones_array)) {
        redirect($baseurl, "Debe existir al menos un recurso o una evaluación para los temas seleccionados.", 3);
    }

    // ✅ Crear registro en learningstylesurvey_paths
    $ruta = new stdClass();
    $ruta->courseid = $courseid;
    $ruta->userid = $USER->id;
    $ruta->cmid = $cmid;
    $ruta->name = $nombre;
    $ruta->timecreated = time();
    $pathid = $DB->insert_record('learningstylesurvey_paths', $ruta);

    // ✅ Guardar los temas seleccionados en la tabla de asociación
    foreach ($temas_ids as $orden => $tema_id) {
        $record = new stdClass();
        $record->pathid = $pathid;
        $record->temaid = $tema_id;
        $record->orden = $orden + 1;
        $record->isrefuerzo = in_array($tema_id, $refuerzo_ids) ? 1 : 0;
        $DB->insert_record('learningstylesurvey_path_temas', $record);
    }

    // ✅ Insertar archivos en learningstylesurvey_path_files
    foreach ($archivos as $resource) {
        $rec = new stdClass();
        $rec->pathid = $pathid;
        $rec->filename = $resource->filename;
        $rec->steporder = 0;
        $DB->insert_record('learningstylesurvey_path_files', $rec);
    }

    // ✅ Insertar evaluaciones en learningstylesurvey_path_evaluations
    foreach ($evaluaciones_array as $quizid) {
        if ($DB->record_exists('learningstylesurvey_quizzes', ['id' => $quizid])) {
            $rec = new stdClass();
            $rec->pathid = $pathid;
            $rec->quizid = $quizid;
            $DB->insert_record('learningstylesurvey_path_evaluations', $rec);
        }
    }

    // ✅ Insertar pasos según el orden establecido
    $stepnumber = 1;
    $orden_items_array = [];
    
    if (!empty($orden_items)) {
        foreach (explode('|', $orden_items) as $item) {
            if (!empty($item)) {
                $parts = explode(':', $item);
                if (count($parts) == 2) {
                    $orden_items_array[] = ['type' => $parts[0], 'id' => $parts[1]];
                }
            }
        }
    }

    foreach ($orden_items_array as $item) {
        if ($item['type'] === 'tema') {
            // Insertar recursos del tema
            $archivos_tema = $DB->get_records('learningstylesurvey_resources', [
                'courseid' => $courseid,
                'tema' => $item['id']
            ]);
            
            foreach ($archivos_tema as $resource) {
                $step = new stdClass();
                $step->pathid = $pathid;
                $step->stepnumber = $stepnumber++;
                $step->resourceid = $resource->id;
                $step->istest = 0;
                $step->passredirect = 0;
                $step->failredirect = 0;
                $DB->insert_record('learningpath_steps', $step);
            }
        } else if ($item['type'] === 'evaluacion') {
            // Insertar evaluación
            if ($DB->record_exists('learningstylesurvey_quizzes', ['id' => $item['id']])) {
                $step = new stdClass();
                $step->pathid = $pathid;
                $step->stepnumber = $stepnumber++;
                $step->resourceid = $item['id'];
                $step->istest = 1;
                
                // Configurar saltos para evaluaciones
                $pass_redirect = 0;
                $fail_redirect = 0;
                
                if (isset($saltos_pass[$item['id']])) {
                    $salto = $saltos_pass[$item['id']];
                    if ($salto['type'] === 'tema') {
                        // Encontrar el primer recurso del tema de destino
                        $primer_recurso = $DB->get_record_sql(
                            "SELECT id FROM {learningstylesurvey_resources} 
                             WHERE courseid = ? AND tema = ? 
                             ORDER BY id ASC LIMIT 1", 
                            [$courseid, $salto['id']]
                        );
                        if ($primer_recurso) {
                            $pass_redirect = $primer_recurso->id;
                        }
                    }
                }
                
                if (isset($saltos_fail[$item['id']])) {
                    $salto = $saltos_fail[$item['id']];
                    if ($salto['type'] === 'tema') {
                        // Encontrar el primer recurso del tema de refuerzo
                        $primer_recurso = $DB->get_record_sql(
                            "SELECT id FROM {learningstylesurvey_resources} 
                             WHERE courseid = ? AND tema = ? 
                             ORDER BY id ASC LIMIT 1", 
                            [$courseid, $salto['id']]
                        );
                        if ($primer_recurso) {
                            $fail_redirect = $primer_recurso->id;
                        }
                    }
                }
                
                $step->passredirect = $pass_redirect;
                $step->failredirect = $fail_redirect;
                $DB->insert_record('learningpath_steps', $step);
            }
        }
    }

    redirect($returnurl, "Ruta creada exitosamente.", 2);
}

// ✅ Cargar temas y evaluaciones - Filtrados por usuario
$temas = $DB->get_records('learningstylesurvey_temas', ['courseid' => $courseid, 'userid' => $USER->id]);
$evaluaciones = $DB->get_records('learningstylesurvey_quizzes', ['courseid' => $courseid, 'userid' => $USER->id]);

echo $OUTPUT->header();
echo $OUTPUT->heading("Crear Ruta de Aprendizaje");
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
        padding: 10px 12px;
        border: 2px solid #e1e5e9;
        border-radius: 8px;
        font-size: 14px;
    }
    .form-control:focus {
        outline: none;
        border-color: #007bff;
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
    }
    .btn-modern {
        padding: 8px 16px;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .btn-primary-modern {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
    }
    .btn-success-modern {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
    }
    .btn-secondary-modern {
        background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        color: white;
    }
    .route-item {
        background: white;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 10px;
        cursor: move;
        transition: all 0.3s ease;
        position: relative;
    }
    .route-item:hover {
        border-color: #007bff;
        box-shadow: 0 2px 8px rgba(0, 123, 255, 0.15);
    }
    .route-item.tema {
        border-left: 5px solid #28a745;
    }
    .route-item.evaluacion {
        border-left: 5px solid #dc3545;
    }
    .route-item-header {
        display: flex;
        justify-content: between;
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
        color: #f57c00;
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
</style>

<form method="post" id="route-form">
    <div class="form-group">
        <label>📝 Nombre de la ruta:</label>
        <input type="text" name="nombre" required class="form-control" placeholder="Ingrese el nombre de la ruta de aprendizaje">
    </div>

    <div class="route-builder">
        <!-- Panel Izquierdo: Controles -->
        <div class="left-panel">
            <div class="form-group">
                <label>📚 Agregar Temas:</label>
                <select id="tema_select" class="form-control">
                    <option value="">-- Seleccione un tema --</option>
                    <?php foreach ($temas as $tema): ?>
                        <option value="<?php echo $tema->id; ?>" data-name="<?php echo htmlspecialchars($tema->tema); ?>">
                            <?php echo format_string($tema->tema); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn-modern btn-success-modern" onclick="agregarTema()" style="margin-top: 8px;">
                    ➕ Agregar Tema
                </button>
                <small style="color: #6c757d; display: block; margin-top: 5px;">
                    Los archivos asociados se incluirán automáticamente
                </small>
            </div>

            <div class="form-group">
                <label>📋 Agregar Evaluaciones:</label>
                <select id="evaluacion_select" class="form-control">
                    <option value="">-- Seleccione una evaluación --</option>
                    <?php foreach ($evaluaciones as $eval): ?>
                        <option value="<?php echo $eval->id; ?>" data-name="<?php echo htmlspecialchars($eval->name); ?>">
                            <?php echo format_string($eval->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn-modern btn-success-modern" onclick="agregarEvaluacion()" style="margin-top: 8px;">
                    ➕ Agregar Evaluación
                </button>
            </div>

            <div class="form-group">
                <button type="submit" class="btn-modern btn-primary-modern" style="width: 100%; padding: 12px;">
                    💾 Guardar Ruta
                </button>
            </div>
        </div>

        <!-- Panel Derecho: Vista Previa -->
        <div class="preview-panel">
            <h4 style="margin-bottom: 20px; color: #333;">🔍 Vista Previa de la Ruta</h4>
            <div id="route-preview">
                <div class="empty-preview">
                    <div class="icon">📋</div>
                    <h5>Ruta vacía</h5>
                    <p>Agregue temas y evaluaciones para construir su ruta de aprendizaje</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Campos ocultos para envío -->
    <input type="hidden" name="temas_hidden" id="temas_hidden">
    <input type="hidden" name="evaluacion_hidden" id="evaluacion_hidden">
    <input type="hidden" name="orden_hidden" id="orden_hidden">
</form>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
let routeItems = [];
let itemCounter = 0;

// Inicializar Sortable para drag & drop
document.addEventListener('DOMContentLoaded', function() {
    const previewContainer = document.getElementById('route-preview');
    new Sortable(previewContainer, {
        animation: 150,
        ghostClass: 'sortable-placeholder',
        onEnd: function() {
            updateOrder();
            updateHiddenFields();
        }
    });
});

function agregarTema() {
    const select = document.getElementById('tema_select');
    const temaId = select.value;
    const temaName = select.options[select.selectedIndex].getAttribute('data-name');

    if (temaId && !routeItems.find(item => item.type === 'tema' && item.id === temaId)) {
        const newItem = {
            type: 'tema',
            id: temaId,
            name: temaName,
            order: routeItems.length + 1,
            uniqueId: ++itemCounter,
            isrefuerzo: false
        };
        
        routeItems.push(newItem);
        renderRoutePreview();
        updateHiddenFields();
        select.value = '';
    } else if (temaId) {
        alert('Este tema ya está agregado a la ruta');
    }
}

function agregarEvaluacion() {
    const select = document.getElementById('evaluacion_select');
    const evalId = select.value;
    const evalName = select.options[select.selectedIndex].getAttribute('data-name');

    if (evalId && !routeItems.find(item => item.type === 'evaluacion' && item.id === evalId)) {
        const newItem = {
            type: 'evaluacion',
            id: evalId,
            name: evalName,
            order: routeItems.length + 1,
            uniqueId: ++itemCounter,
            passredirect: null,
            failredirect: null
        };
        
        routeItems.push(newItem);
        renderRoutePreview();
        updateHiddenFields();
        select.value = '';
    } else if (evalId) {
        alert('Esta evaluación ya está agregada a la ruta');
    }
}

function removeItem(uniqueId) {
    routeItems = routeItems.filter(item => item.uniqueId !== uniqueId);
    renderRoutePreview();
    updateHiddenFields();
}

function moveItem(uniqueId, direction) {
    const index = routeItems.findIndex(item => item.uniqueId === uniqueId);
    if (index === -1) return;
    
    const newIndex = direction === 'up' ? index - 1 : index + 1;
    if (newIndex < 0 || newIndex >= routeItems.length) return;
    
    // Intercambiar elementos
    [routeItems[index], routeItems[newIndex]] = [routeItems[newIndex], routeItems[index]];
    
    renderRoutePreview();
    updateHiddenFields();
}

function renderRoutePreview() {
    const container = document.getElementById('route-preview');
    
    if (routeItems.length === 0) {
        container.innerHTML = `
            <div class="empty-preview">
                <div class="icon">📋</div>
                <h5>Ruta vacía</h5>
                <p>Agregue temas y evaluaciones para construir su ruta de aprendizaje</p>
            </div>
        `;
        return;
    }

    let html = '';
    routeItems.forEach((item, index) => {
        const typeIcon = item.type === 'tema' ? '📚' : '📋';
        const typeLabel = item.type === 'tema' ? 'Tema' : 'Evaluación';
        const isRefuerzo = item.isrefuerzo ? ' (Refuerzo)' : '';
        const refuerzoClass = item.isrefuerzo ? ' refuerzo' : '';
        
        html += `
            <div class="route-item ${item.type}${refuerzoClass}" data-unique-id="${item.uniqueId}">
                <div class="route-item-header">
                    <span class="route-item-type">${typeIcon} ${typeLabel}${isRefuerzo}</span>
                    <div class="route-item-controls">
                        <button type="button" class="btn-modern btn-sm" onclick="editItem(${item.uniqueId})" title="Configurar">⚙️</button>
                        ${index > 0 ? `<button type="button" class="btn-modern btn-sm" onclick="moveItem(${item.uniqueId}, 'up')" title="Mover arriba">⬆️</button>` : ''}
                        ${index < routeItems.length - 1 ? `<button type="button" class="btn-modern btn-sm" onclick="moveItem(${item.uniqueId}, 'down')" title="Mover abajo">⬇️</button>` : ''}
                        <button type="button" class="btn-modern btn-sm" onclick="removeItem(${item.uniqueId})" title="Eliminar" style="background: #dc3545; color: white;">🗑️</button>
                    </div>
                </div>
                <div style="font-weight: 600; color: #333;">
                    ${index + 1}. ${item.name}
                </div>
                <div style="font-size: 12px; color: #6c757d; margin-top: 5px;">
                    ${getItemDescription(item)}
                </div>
                ${getItemConfigInfo(item)}
            </div>
        `;
    });

    container.innerHTML = html;
}

function getItemDescription(item) {
    if (item.type === 'tema') {
        return item.isrefuerzo ? 'Tema de refuerzo - solo aparece cuando es necesario' : 'Incluye todos los recursos asociados';
    } else {
        return 'Evaluación con preguntas';
    }
}

function getItemConfigInfo(item) {
    if (item.type === 'evaluacion') {
        let saltos = [];
        if (item.passredirect) {
            const passItem = routeItems.find(r => r.uniqueId == item.passredirect);
            saltos.push(`✅ Aprueba → ${passItem ? passItem.name : 'Item eliminado'}`);
        }
        if (item.failredirect) {
            const failItem = routeItems.find(r => r.uniqueId == item.failredirect);
            saltos.push(`❌ Reprueba → ${failItem ? failItem.name : 'Item eliminado'}`);
        }
        if (saltos.length > 0) {
            return `<div style="font-size: 11px; color: #007bff; margin-top: 3px;">${saltos.join(' | ')}</div>`;
        }
    }
    return '';
}

function editItem(uniqueId) {
    const item = routeItems.find(item => item.uniqueId === uniqueId);
    if (!item) return;
    
    if (item.type === 'tema') {
        editTemaItem(item);
    } else {
        editEvaluacionItem(item);
    }
}

function editTemaItem(item) {
    const isRefuerzo = item.isrefuerzo || false;
    
    const html = `
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-width: 400px;">
            <h4>⚙️ Configurar Tema: ${item.name}</h4>
            <div style="margin: 15px 0;">
                <label style="display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" id="tema-refuerzo-${item.uniqueId}" ${isRefuerzo ? 'checked' : ''}>
                    <span>🔄 Es tema de refuerzo</span>
                </label>
                <small style="color: #6c757d; display: block; margin-top: 5px;">
                    Los temas de refuerzo solo aparecen cuando el estudiante necesita apoyo adicional
                </small>
            </div>
            <div style="margin-top: 20px;">
                <button type="button" class="btn-modern btn-primary-modern" onclick="saveTemaConfig(${item.uniqueId})">💾 Guardar</button>
                <button type="button" class="btn-modern btn-secondary-modern" onclick="closeModal()" style="margin-left: 10px;">❌ Cancelar</button>
            </div>
        </div>
    `;
    
    showModal(html);
}

function editEvaluacionItem(item) {
    const temaOptions = routeItems.filter(r => r.type === 'tema').map(r => 
        `<option value="${r.uniqueId}" ${item.passredirect == r.uniqueId ? 'selected' : ''}>${r.name}</option>`
    ).join('');
    
    // Permitir saltos a cualquier tema, no solo refuerzo
    const failOptions = routeItems.filter(r => r.type === 'tema').map(r => {
        const refuerzoLabel = r.isrefuerzo ? ' (Refuerzo)' : '';
        return `<option value="${r.uniqueId}" ${item.failredirect == r.uniqueId ? 'selected' : ''}>${r.name}${refuerzoLabel}</option>`;
    }).join('');
    
    const html = `
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-width: 500px;">
            <h4>⚙️ Configurar Evaluación: ${item.name}</h4>
            
            <div style="margin: 15px 0;">
                <label style="font-weight: 600; margin-bottom: 5px; display: block;">✅ Si aprueba, ir a:</label>
                <select id="eval-pass-${item.uniqueId}" style="width: 100%; padding: 8px;">
                    <option value="">Programar Salto</option>
                    ${temaOptions}
                </select>
            </div>
            
            <div style="margin: 15px 0;">
                <label style="font-weight: 600; margin-bottom: 5px; display: block;">❌ Si reprueba, ir a:</label>
                <select id="eval-fail-${item.uniqueId}" style="width: 100%; padding: 8px;">
                    <option value="">Programar Salto</option>
                    ${failOptions}
                </select>
                <small style="color: #6c757d; display: block; margin-top: 5px;">
                    Puede saltar a cualquier tema. Los temas de refuerzo se recomiendan para apoyo adicional.
                </small>
            </div>
            
            <div style="margin-top: 20px;">
                <button type="button" class="btn-modern btn-primary-modern" onclick="saveEvaluacionConfig(${item.uniqueId})">💾 Guardar</button>
                <button type="button" class="btn-modern btn-secondary-modern" onclick="closeModal()" style="margin-left: 10px;">❌ Cancelar</button>
            </div>
        </div>
    `;
    
    showModal(html);
}

function saveTemaConfig(uniqueId) {
    const item = routeItems.find(item => item.uniqueId === uniqueId);
    if (!item) return;
    
    item.isrefuerzo = document.getElementById(`tema-refuerzo-${uniqueId}`).checked;
    
    closeModal();
    renderRoutePreview();
    updateHiddenFields();
}

function saveEvaluacionConfig(uniqueId) {
    const item = routeItems.find(item => item.uniqueId === uniqueId);
    if (!item) return;
    
    const passValue = document.getElementById(`eval-pass-${uniqueId}`).value;
    const failValue = document.getElementById(`eval-fail-${uniqueId}`).value;
    
    item.passredirect = passValue || null;
    item.failredirect = failValue || null;
    
    closeModal();
    renderRoutePreview();
    updateHiddenFields();
}

function showModal(html) {
    const existingModal = document.getElementById('config-modal');
    if (existingModal) {
        existingModal.remove();
    }
    
    const modal = document.createElement('div');
    modal.id = 'config-modal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    `;
    modal.innerHTML = html;
    
    document.body.appendChild(modal);
    
    // Cerrar al hacer clic fuera
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });
}

function closeModal() {
    const modal = document.getElementById('config-modal');
    if (modal) {
        modal.remove();
    }
}

function updateOrder() {
    const container = document.getElementById('route-preview');
    const items = container.querySelectorAll('.route-item[data-unique-id]');
    
    const newOrder = [];
    items.forEach(element => {
        const uniqueId = parseInt(element.getAttribute('data-unique-id'));
        const item = routeItems.find(item => item.uniqueId === uniqueId);
        if (item) {
            newOrder.push(item);
        }
    });
    
    routeItems = newOrder;
    renderRoutePreview();
}

function updateHiddenFields() {
    const temas = routeItems.filter(item => item.type === 'tema').map(item => item.id);
    const evaluaciones = routeItems.filter(item => item.type === 'evaluacion').map(item => item.id);
    const orden = routeItems.map(item => `${item.type}:${item.id}`);
    
    // Campos de refuerzo
    const temasRefuerzo = routeItems.filter(item => item.type === 'tema' && item.isrefuerzo).map(item => item.id);
    
    // Campos de saltos de evaluación
    const saltosAprueba = [];
    const saltosReprueba = [];
    
    routeItems.filter(item => item.type === 'evaluacion').forEach(item => {
        if (item.passredirect) {
            const targetItem = routeItems.find(r => r.uniqueId == item.passredirect);
            if (targetItem) {
                saltosAprueba.push(`${item.id}:${targetItem.type}:${targetItem.id}`);
            }
        }
        if (item.failredirect) {
            const targetItem = routeItems.find(r => r.uniqueId == item.failredirect);
            if (targetItem) {
                saltosReprueba.push(`${item.id}:${targetItem.type}:${targetItem.id}`);
            }
        }
    });
    
    document.getElementById('temas_hidden').value = temas.join(',');
    document.getElementById('evaluacion_hidden').value = evaluaciones.join(',');
    document.getElementById('orden_hidden').value = orden.join('|');
    
    // Crear campos ocultos para refuerzo y saltos si no existen
    if (!document.getElementById('refuerzo_hidden')) {
        const refuerzoInput = document.createElement('input');
        refuerzoInput.type = 'hidden';
        refuerzoInput.id = 'refuerzo_hidden';
        refuerzoInput.name = 'temas_refuerzo';
        document.getElementById('route-form').appendChild(refuerzoInput);
    }
    
    if (!document.getElementById('saltos_aprueba_hidden')) {
        const saltosApruebaInput = document.createElement('input');
        saltosApruebaInput.type = 'hidden';
        saltosApruebaInput.id = 'saltos_aprueba_hidden';
        saltosApruebaInput.name = 'saltos_aprueba';
        document.getElementById('route-form').appendChild(saltosApruebaInput);
    }
    
    if (!document.getElementById('saltos_reprueba_hidden')) {
        const saltosRepruebaInput = document.createElement('input');
        saltosRepruebaInput.type = 'hidden';
        saltosRepruebaInput.id = 'saltos_reprueba_hidden';
        saltosRepruebaInput.name = 'saltos_reprueba';
        document.getElementById('route-form').appendChild(saltosRepruebaInput);
    }
    
    document.getElementById('refuerzo_hidden').value = temasRefuerzo.join(',');
    document.getElementById('saltos_aprueba_hidden').value = saltosAprueba.join('|');
    document.getElementById('saltos_reprueba_hidden').value = saltosReprueba.join('|');
}

// Actualizar contadores al cargar la página
updateHiddenFields();
</script>

<?php
$urlreturn = new moodle_url('/mod/learningstylesurvey/path/learningpath.php', ['courseid' => $courseid]);
echo "<br><a href='{$urlreturn}' class='btn btn-secondary'>Regresar al menú anterior</a>";
echo $OUTPUT->footer();
?>
