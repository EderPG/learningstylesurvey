<?php
require_once("../../../config.php");
require_once("$CFG->libdir/formslib.php");
global $DB, $USER, $PAGE, $OUTPUT;

$courseid = required_param('courseid', PARAM_INT);
$pathid = optional_param('pathid', 0, PARAM_INT);
$stepid = optional_param('stepid', 0, PARAM_INT);
$tema_salto = optional_param('tema_salto', 0, PARAM_INT); // Para saltos adaptativos por tema
$tema_refuerzo = optional_param('tema_refuerzo', 0, PARAM_INT); // Para temas de refuerzo
$cmid = optional_param('cmid', 0, PARAM_INT);
$completed = optional_param('completed', 0, PARAM_INT); // Para mostrar mensaje de finalización

// Si no se proporciona cmid, obtenerlo del contexto actual
if (!$cmid) {
    $modinfo = get_fast_modinfo($courseid);
    $cms = $modinfo->get_instances_of('learningstylesurvey');
    if (!empty($cms)) {
        $firstcm = reset($cms);
        $cmid = $firstcm->id;
    }
}

require_login();

// Función para mostrar un recurso
function mostrar_recurso($resource) {
    global $CFG, $COURSE;
    $fileurl = new moodle_url('/mod/learningstylesurvey/resource/ver_recurso.php', [
        'filename' => $resource->filename,
        'courseid' => $resource->courseid,
        'serve' => 1
    ]);
    $filepath = $CFG->dataroot . "/learningstylesurvey/" . $resource->courseid . "/" . $resource->filename;
    $ext = strtolower(pathinfo($resource->filename, PATHINFO_EXTENSION));
    
    echo "<h3>" . format_string($resource->name ?: 'Recurso') . "</h3>";
    
    // Verificar si el archivo existe físicamente
    if (!file_exists($filepath)) {
        echo "<div class='alert alert-danger'>❌ <strong>Archivo no encontrado:</strong> {$resource->filename}</div>";
        echo "<p>El archivo puede haber sido movido o eliminado del servidor.</p>";
        return;
    }
    
    // Mostrar según el tipo de archivo
    if (in_array($ext, ['jpg','jpeg','png','gif','webp','svg'])) {
        // Imágenes
        echo "<img src='$fileurl' style='max-width:100%; height:auto; margin-bottom:20px; border: 1px solid #ddd; border-radius: 8px;'>";
        
    } elseif ($ext === 'pdf') {
        // PDFs
        echo "<iframe src='$fileurl' style='width:100%; height:600px; border:none; border-radius: 8px;'></iframe>";
        
    } elseif (in_array($ext, ['mp4','webm','avi','mov'])) {
        // Videos
        echo "<video controls style='width:100%; max-height:500px; border-radius: 8px;'>";
        echo "<source src='$fileurl' type='video/$ext'>";
        echo "Tu navegador no soporta video HTML5.";
        echo "</video>";
        
    } elseif (in_array($ext, ['mp3','wav','ogg'])) {
        // Audio
        echo "<audio controls style='width:100%; margin-bottom:20px;'>";
        echo "<source src='$fileurl' type='audio/$ext'>";
        echo "Tu navegador no soporta audio HTML5.";
        echo "</audio>";
        
    } elseif ($ext === 'txt') {
        // Archivos de texto - mostrar contenido
        $content = file_get_contents($filepath);
        if ($content !== false) {
            echo "<div style='background:#f8f9fa; padding:20px; border:1px solid #dee2e6; border-radius:8px; font-family:monospace; white-space:pre-wrap; max-height:500px; overflow-y:auto;'>";
            echo htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
            echo "</div>";
        } else {
            echo "<div class='alert alert-warning'>No se pudo leer el contenido del archivo de texto.</div>";
        }
        
    } elseif (in_array($ext, ['doc','docx','xls','xlsx','ppt','pptx'])) {
        // Archivos de Microsoft Office - usar Office Online Viewer
        $encoded_url = urlencode($fileurl);
        echo "<iframe src='https://view.officeapps.live.com/op/embed.aspx?src={$encoded_url}' style='width:100%; height:600px; border:none; border-radius: 8px;'></iframe>";
        echo "<p><small>📝 <strong>Nota:</strong> Si el visor no funciona, <a href='$fileurl' target='_blank'>descarga el archivo</a> para abrirlo localmente.</small></p>";
        
    } elseif (in_array($ext, ['html','htm'])) {
        // Archivos HTML
        echo "<iframe src='$fileurl' style='width:100%; height:600px; border:1px solid #ddd; border-radius: 8px;'></iframe>";
        
    } else {
        // Otros tipos de archivo - enlace de descarga mejorado
        $filesize = file_exists($filepath) ? human_filesize(filesize($filepath)) : 'Tamaño desconocido';
        echo "<div style='background:#f8f9fa; padding:20px; border:1px solid #dee2e6; border-radius:8px; text-align:center;'>";
        echo "<p>📁 <strong>Archivo:</strong> {$resource->filename}</p>";
        echo "<p>📊 <strong>Tamaño:</strong> {$filesize}</p>";
        echo "<a href='$fileurl' target='_blank' class='btn btn-primary' style='background:#007bff; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>📥 Descargar archivo</a>";
        echo "</div>";
    }
}

// Función auxiliar para formatear tamaño de archivo
function human_filesize($size, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    return round($size, $precision) . ' ' . $units[$i];
}
$context = context_course::instance($courseid);
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/mod/learningstylesurvey/path/vista_estudiante.php', ['courseid'=>$courseid,'pathid'=>$pathid]));
$PAGE->set_title("Ruta de Aprendizaje");
$PAGE->set_heading("Ruta de Aprendizaje");

// Obtener estilo más reciente del usuario
$userstyle = $DB->get_record_sql("
    SELECT style
    FROM {learningstylesurvey_userstyles}
    WHERE userid = ?
    ORDER BY timecreated DESC
    LIMIT 1
", [$USER->id]);

$show_warning = false;
if (!$userstyle) {
    $show_warning = true;
    echo $OUTPUT->header();
    echo "<div class='container' style='max-width:600px; margin:40px auto;'>";
    echo "<div class='alert alert-warning' style='font-size:18px; background:#fff3cd; color:#856404; border:1px solid #ffeeba; padding:20px; border-radius:8px;'>";
    echo "<strong>¡Atención!</strong> Para acceder a la ruta de aprendizaje primero debes contestar la <b>encuesta de estilos de aprendizaje</b>.";
    echo "</div>";
    // Botón para regresar al menú principal del plugin
    if ($cmid) {
        $viewurl = new moodle_url('/mod/learningstylesurvey/view.php', ['id' => $cmid]);
        echo html_writer::link($viewurl, 'Regresar al menú principal', ['class' => 'btn btn-primary', 'style' => 'font-size:18px; margin-top:20px;']);
    }
    echo "</div>";
    echo $OUTPUT->footer();
    exit;
}
$style = $userstyle->style; // El estilo ya viene normalizado desde la base de datos

// Debug temporal - mostrar información del filtrado
$debug_info = optional_param('debug', 0, PARAM_INT); // Debug desactivado
if ($debug_info) {
    echo "<div class='alert alert-info'>";
    echo "<h4>Debug - Información de filtrado:</h4>";
    echo "<p><strong>Usuario:</strong> {$USER->id}</p>";
    echo "<p><strong>Estilo original:</strong> {$userstyle->style}</p>";
    echo "<p><strong>Estilo normalizado:</strong> {$style}</p>";
    echo "<p><strong>Curso:</strong> {$courseid}</p>";
    
    // Mostrar recursos disponibles
    $all_resources = $DB->get_records('learningstylesurvey_resources', ['courseid' => $courseid]);
    echo "<p><strong>Recursos totales en el curso:</strong> " . count($all_resources) . "</p>";
    
    $style_resources = $DB->get_records('learningstylesurvey_resources', [
        'courseid' => $courseid,
        'style' => $style
    ]);
    echo "<p><strong>Recursos para estilo '{$style}':</strong> " . count($style_resources) . "</p>";
    
    if ($style_resources) {
        echo "<ul>";
        foreach ($style_resources as $res) {
            echo "<li>ID: {$res->id}, Tema: {$res->tema}, Archivo: {$res->filename}</li>";
        }
        echo "</ul>";
    }
    echo "</div>";
}

// Obtener ruta más reciente si no se pasa pathid
if (!$pathid) {
    $lastroute = $DB->get_record_sql("
        SELECT id 
        FROM {learningstylesurvey_paths} 
        WHERE courseid = ? AND cmid = ?
        ORDER BY timecreated DESC LIMIT 1
    ", [$courseid, $cmid]);
    if (!$lastroute) {
        throw new moodle_exception('No se encontró ninguna ruta para esta actividad.');
    }
    $pathid = $lastroute->id;
}

// --- FLUJO ADAPTADO ---
echo $OUTPUT->header();
echo "<div class='container' style='max-width:900px; margin:20px auto;'>";
echo "<h2>Ruta de Aprendizaje (" . ucfirst($style) . ")</h2>";

// Mostrar mensaje de finalización si se completó la ruta
if ($completed) {
    echo "<div class='alert alert-success alert-dismissible' style='margin-bottom:30px;'>";
    echo "<button type='button' class='close' data-dismiss='alert'>&times;</button>";
    echo "<h4>🎉 ¡Felicitaciones!</h4>";
    echo "<p>Has completado exitosamente la ruta de aprendizaje.</p>";
    if ($cmid) {
        $menuurl = new moodle_url('/mod/learningstylesurvey/view.php', ['id'=>$cmid]);
        echo "<a href='{$menuurl}' class='btn btn-primary'>Regresar al menú principal</a>";
    }
    echo "</div>";
    echo "</div>";
    echo $OUTPUT->footer();
    exit;
}

// Manejar saltos adaptativos por tema
if ($tema_salto) {
    // Mostrar recursos del tema asignado por salto
    $tema = $DB->get_record('learningstylesurvey_temas', ['id' => $tema_salto]);
    if ($tema) {
        echo "<div class='alert alert-success'>Has sido dirigido al tema: <strong>" . format_string($tema->tema) . "</strong></div>";
        $recursos = $DB->get_records('learningstylesurvey_resources', [
            'tema' => $tema_salto,
            'style' => $style,
            'courseid' => $courseid
        ]);
        
        if ($recursos) {
            $resource = reset($recursos); // Tomar el primer recurso del tema
            
            // Mostrar título del tema de salto
            echo "<div style='background:#d4edda; border-left:4px solid #28a745; padding:15px; margin-bottom:20px; border-radius:5px;'>";
            echo "<h3 style='margin:0; color:#155724;'>🎯 " . format_string($tema->tema) . " (Tema asignado)</h3>";
            echo "</div>";
            
            mostrar_recurso($resource);
            
            // Buscar el siguiente paso después de este salto en la ruta
            $current_step = $DB->get_record_sql("
                SELECT s.* FROM {learningpath_steps} s
                JOIN {learningstylesurvey_resources} r ON s.resourceid = r.id
                WHERE s.pathid = ? AND r.tema = ? AND s.istest = 0
                ORDER BY s.stepnumber ASC LIMIT 1
            ", [$pathid, $tema_salto]);
            
            if ($current_step) {
                // Buscar el siguiente paso en la ruta después de este tema
                $next_step = $DB->get_record_sql("
                    SELECT s.* FROM {learningpath_steps} s
                    JOIN {learningstylesurvey_resources} r ON s.resourceid = r.id
                    WHERE s.pathid = ? AND r.style = ? AND s.stepnumber > ? AND s.istest = 0
                    ORDER BY s.stepnumber ASC LIMIT 1
                ", [$pathid, $style, $current_step->stepnumber]);
                
                if ($next_step) {
                    // Actualizar progreso del usuario al siguiente paso
                    $progress = $DB->get_record('learningstylesurvey_user_progress', [
                        'userid' => $USER->id,
                        'pathid' => $pathid
                    ]);
                    
                    if ($progress) {
                        $progress->current_stepid = $next_step->id;
                        $progress->timemodified = time();
                        $DB->update_record('learningstylesurvey_user_progress', $progress);
                    } else {
                        $new_progress = (object)[
                            'userid' => $USER->id,
                            'pathid' => $pathid,
                            'current_stepid' => $next_step->id,
                            'status' => 'inprogress',
                            'timemodified' => time()
                        ];
                        $DB->insert_record('learningstylesurvey_user_progress', $new_progress);
                    }
                    
                    // Botón para continuar al siguiente paso
                    $nexturl = new moodle_url('/mod/learningstylesurvey/path/vista_estudiante.php', [
                        'courseid' => $courseid,
                        'pathid' => $pathid,
                        'stepid' => $next_step->id
                    ]);
                    echo "<div style='margin-top:20px;'>";
                    echo "<a href='" . $nexturl->out() . "' class='btn btn-success'>Continuar con la ruta</a>";
                    echo "</div>";
                } else {
                    // No hay más pasos, la ruta está completa
                    echo "<div class='alert alert-success' style='margin-top:20px; text-align:center; padding:25px;'>";
                    echo "<h4>🎉 ¡Has completado la ruta de aprendizaje!</h4>";
                    echo "<p>¡Felicitaciones! Has terminado exitosamente todos los contenidos.</p>";
                    if ($cmid) {
                        $menuurl = new moodle_url('/mod/learningstylesurvey/view.php', ['id'=>$cmid]);
                        echo "<a href='{$menuurl}' class='btn btn-primary btn-lg' style='margin-top:15px;'>Regresar al menú principal</a>";
                    }
                    echo "</div>";
                }
            } else {
                // Este tema no está en la ruta, regresar al flujo normal
                $returnurl = new moodle_url('/mod/learningstylesurvey/path/vista_estudiante.php', [
                    'courseid' => $courseid,
                    'pathid' => $pathid
                ]);
                echo "<div style='margin-top:20px;'>";
                echo "<a href='" . $returnurl->out() . "' class='btn btn-success'>Continuar con la ruta</a>";
                echo "</div>";
            }
        } else {
            echo "<div class='alert alert-warning'>No hay recursos para tu estilo de aprendizaje en este tema.</div>";
        }
        echo "</div>";
        echo $OUTPUT->footer();
        exit;
    }
}

if ($tema_refuerzo) {
    // Mostrar tema de refuerzo
    $tema = $DB->get_record('learningstylesurvey_temas', ['id' => $tema_refuerzo]);
    if ($tema) {
        echo "<div class='alert alert-warning'>Necesitas refuerzo en el tema: <strong>" . format_string($tema->tema) . "</strong></div>";
        
        // DEBUG: Agregar información de debug
        if ($debug_info) {
            echo "<div class='alert alert-info'>";
            echo "<h4>Debug - Recursos de refuerzo:</h4>";
            echo "<p><strong>Tema refuerzo ID:</strong> {$tema_refuerzo}</p>";
            echo "<p><strong>Estilo usuario:</strong> {$style}</p>";
            echo "<p><strong>Curso:</strong> {$courseid}</p>";
            
            // Buscar TODOS los recursos de este tema sin filtro de estilo
            $todos_recursos = $DB->get_records('learningstylesurvey_resources', [
                'tema' => $tema_refuerzo,
                'courseid' => $courseid
            ]);
            echo "<p><strong>Recursos totales para este tema:</strong> " . count($todos_recursos) . "</p>";
            if ($todos_recursos) {
                echo "<ul>";
                foreach ($todos_recursos as $r) {
                    echo "<li>ID: {$r->id}, Estilo: '{$r->style}', Usuario: {$r->userid}, Archivo: {$r->filename}</li>";
                }
                echo "</ul>";
            }
            echo "</div>";
        }
        
        // Buscar recursos de refuerzo para este tema y estilo
        $recursos_refuerzo = $DB->get_records('learningstylesurvey_resources', [
            'tema' => $tema_refuerzo,
            'style' => $style,
            'courseid' => $courseid
        ]);
        
        if ($recursos_refuerzo) {
            $resource = reset($recursos_refuerzo);
            // Mostrar título del tema de refuerzo
            echo "<div style='background:#fff3cd; border-left:4px solid #ffc107; padding:15px; margin-bottom:20px; border-radius:5px;'>";
            echo "<h3 style='margin:0; color:#856404;'>🔄 " . format_string($tema->tema) . " (Refuerzo)</h3>";
            echo "</div>";
            
            mostrar_recurso($resource);
        } else {
            echo "<div class='alert alert-info'>No hay recursos de refuerzo específicos para tu estilo. Revisa el material general del tema.</div>";
            
            // Buscar recursos sin filtro de estilo como fallback
            $recursos_generales = $DB->get_records('learningstylesurvey_resources', [
                'tema' => $tema_refuerzo,
                'courseid' => $courseid
            ]);
            
            if ($recursos_generales) {
                echo "<div class='alert alert-info'>Mostrando recursos generales del tema:</div>";
                $resource = reset($recursos_generales);
                echo "<div style='background:#fff3cd; border-left:4px solid #ffc107; padding:15px; margin-bottom:20px; border-radius:5px;'>";
                echo "<h3 style='margin:0; color:#856404;'>🔄 " . format_string($tema->tema) . " (Refuerzo)</h3>";
                echo "</div>";
                mostrar_recurso($resource);
            }
        }
        
        // Buscar el examen que se reprobó para permitir reintento
        // Buscar exámenes que tengan salto de fallo configurado hacia este tema de refuerzo
        $lastquiz = $DB->get_record_sql("
            SELECT qr.*, s.*, ls_target.stepnumber as target_step 
            FROM {learningstylesurvey_quiz_results} qr
            JOIN {learningpath_steps} s ON s.resourceid = qr.quizid AND s.istest = 1
            JOIN {learningpath_steps} ls_target ON ls_target.stepnumber = s.failredirect AND ls_target.pathid = s.pathid
            JOIN {learningstylesurvey_resources} res ON res.id = ls_target.resourceid
            WHERE qr.userid = ? AND qr.courseid = ? AND res.tema = ? AND qr.score < 70
            ORDER BY qr.timecompleted DESC LIMIT 1
        ", [$USER->id, $courseid, $tema_refuerzo]);
        
        if ($lastquiz) {
            echo "<div style='margin-top:30px; padding: 15px; background: #e7f3ff; border-left: 4px solid #007bff; border-radius: 5px;'>";
            echo "<h4 style='margin-top: 0;'>💡 ¿Listo para el reintento?</h4>";
            echo "<p>Después de estudiar el material de refuerzo, puedes volver a intentar el examen.</p>";
            $retryurl = new moodle_url('/mod/learningstylesurvey/quiz/responder_quiz.php', [
                'id' => $lastquiz->quizid,
                'courseid' => $courseid,
                'embedded' => 1,
                'retry' => 1,
                'cmid' => $cmid
            ]);
            echo "<a href='{$retryurl}' class='btn btn-primary btn-lg'>🔄 Reintentar examen</a>";
            echo "</div>";
        } else {
            // Fallback: buscar cualquier examen reciente reprobado
            $fallback_quiz = $DB->get_record_sql("
                SELECT qr.*, q.name as quiz_name
                FROM {learningstylesurvey_quiz_results} qr
                JOIN {learningstylesurvey_quizzes} q ON q.id = qr.quizid
                WHERE qr.userid = ? AND qr.courseid = ? AND qr.score < 70
                ORDER BY qr.timecompleted DESC LIMIT 1
            ", [$USER->id, $courseid]);
            
            if ($fallback_quiz) {
                echo "<div style='margin-top:30px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 5px;'>";
                echo "<h4 style='margin-top: 0;'>📝 Reintento disponible</h4>";
                echo "<p>Tienes un examen reprobado que puedes volver a intentar: <strong>" . format_string($fallback_quiz->quiz_name) . "</strong></p>";
                $retryurl = new moodle_url('/mod/learningstylesurvey/quiz/responder_quiz.php', [
                    'id' => $fallback_quiz->quizid,
                    'courseid' => $courseid,
                    'embedded' => 1,
                    'retry' => 1,
                    'cmid' => $cmid
                ]);
                echo "<a href='{$retryurl}' class='btn btn-warning btn-lg'>🔄 Reintentar examen</a>";
                echo "</div>";
            }
        }
        
        echo "</div>";
        echo $OUTPUT->footer();
        exit;
    }
}

// Si hay stepid específico, mostrar ese recurso (flujo normal)
if ($stepid) {
    $step = $DB->get_record('learningpath_steps', ['id' => $stepid]);
    if ($step) {
        if ($step->istest) {
            // Si es un examen, redirigir a responder_quiz.php
            $quizurl = new moodle_url('/mod/learningstylesurvey/quiz/responder_quiz.php', [
                'id' => $step->resourceid,
                'courseid' => $courseid,
                'embedded' => 1,
                'cmid' => $cmid
            ]);
            redirect($quizurl);
        } else {
            // Si es un recurso, mostrarlo
            $resource = $DB->get_record('learningstylesurvey_resources', ['id' => $step->resourceid]);
            if ($resource) {
                // Mostrar título del tema actual
                $tema_actual = $DB->get_record('learningstylesurvey_temas', ['id' => $resource->tema]);
                if ($tema_actual) {
                    echo "<div style='background:#e7f3ff; border-left:4px solid #007bff; padding:15px; margin-bottom:20px; border-radius:5px;'>";
                    echo "<h3 style='margin:0; color:#0056b3;'>📚 " . format_string($tema_actual->tema) . "</h3>";
                    echo "</div>";
                }
                
                mostrar_recurso($resource);
                echo "<form method='POST' action='siguiente.php'>
                        <input type='hidden' name='courseid' value='{$courseid}'>
                        <input type='hidden' name='pathid' value='{$pathid}'>
                        <input type='hidden' name='stepid' value='{$step->id}'>
                        <input type='hidden' name='cmid' value='{$cmid}'>
                        <button type='submit' class='btn btn-success' style='margin-top:15px;'>Continuar</button>
                      </form>";
                echo "</div>";
                echo $OUTPUT->footer();
                exit;
            }
        }
    }
}

// Verificar si el ÚLTIMO intento de un examen de ESTA RUTA fue reprobado
$lastquiz = $DB->get_record_sql("
    SELECT qr.*, s.failredirect 
    FROM {learningstylesurvey_quiz_results} qr
    JOIN {learningpath_steps} s ON s.resourceid = qr.quizid AND s.istest = 1
    WHERE qr.userid = ? AND qr.courseid = ? AND s.pathid = ?
    ORDER BY qr.timecompleted DESC 
    LIMIT 1
", [$USER->id, $courseid, $pathid]);

$show_refuerzo = false;
$tema_refuerzo_id = null;
if ($lastquiz && $lastquiz->score < 70 && $lastquiz->failredirect) {
    // Solo mostrar refuerzo si realmente reprobó Y hay un tema de refuerzo configurado
    $tema_refuerzo_id = $lastquiz->failredirect;
    $show_refuerzo = true;
}

// Debug temporal - mostrar información del examen
if ($debug_info) {
    echo "<div class='alert alert-warning'>";
    echo "<h4>Debug - Estado del examen:</h4>";
    if ($lastquiz) {
        echo "<p><strong>Último resultado:</strong> Score {$lastquiz->score}, Quiz ID {$lastquiz->quizid}, Tiempo: " . date('Y-m-d H:i:s', $lastquiz->timecompleted) . "</p>";
        echo "<p><strong>Failredirect:</strong> {$lastquiz->failredirect}</p>";
        echo "<p><strong>Mostrar refuerzo:</strong> " . ($show_refuerzo ? 'SÍ' : 'NO') . "</p>";
    } else {
        echo "<p><strong>No hay resultados de examen para esta ruta</strong></p>";
    }
    echo "</div>";
}

if ($show_refuerzo && $tema_refuerzo_id) {
    // Buscar recursos del tema de refuerzo para el estilo del usuario
    $recursos_refuerzo = $DB->get_records('learningstylesurvey_resources', [
        'tema' => $tema_refuerzo_id,
        'style' => $style,
        'courseid' => $courseid
    ]);
    
    if ($recursos_refuerzo) {
        $resource = reset($recursos_refuerzo); // Tomar el primer recurso
        $tema = $DB->get_record('learningstylesurvey_temas', ['id' => $tema_refuerzo_id]);
        
        echo "<div class='alert alert-warning' style='margin-bottom:20px;'>Has reprobado el examen, accede al tema de refuerzo: <strong>" . format_string($tema->tema) . "</strong></div>";
        
        // Mostrar título del tema de refuerzo
        echo "<div style='background:#fff3cd; border-left:4px solid #ffc107; padding:15px; margin-bottom:20px; border-radius:5px;'>";
        echo "<h3 style='margin:0; color:#856404;'>🔄 " . format_string($tema->tema) . " (Refuerzo)</h3>";
        echo "</div>";
        
        mostrar_recurso($resource);
        
        // Botón para reintentar el examen después del refuerzo
        echo "<div style='margin-top:30px; padding: 15px; background: #e7f3ff; border-left: 4px solid #007bff; border-radius: 5px;'>";
        echo "<h4 style='margin-top: 0;'>💡 ¿Listo para el reintento?</h4>";
        echo "<p>Después de estudiar el material de refuerzo, puedes volver a intentar el examen.</p>";
        $retryurl = new moodle_url('/mod/learningstylesurvey/quiz/responder_quiz.php', [
            'id' => $lastquiz->quizid,
            'courseid' => $courseid,
            'embedded' => 1,
            'retry' => 1,
            'cmid' => $cmid
        ]);
        echo "<a href='{$retryurl}' class='btn btn-primary btn-lg'>🔄 Reintentar examen</a>";
        echo "</div>";
    } else {
        echo "<div class='alert alert-warning'>No hay recursos de refuerzo específicos para tu estilo de aprendizaje.</div>";
    }
} else {
    // Flujo normal: consultar progreso del usuario para mostrar el paso correcto
    $progress = $DB->get_record('learningstylesurvey_user_progress', [
        'userid' => $USER->id,
        'pathid' => $pathid
    ]);
    
    // Debug temporal - comentar cuando funcione
    if ($debug_info) {
        echo "<div class='alert alert-warning'>";
        echo "<h4>Debug - Flujo normal:</h4>";
        echo "<p><strong>Progreso encontrado:</strong> " . ($progress ? "SÍ (Step ID: {$progress->current_stepid})" : "NO") . "</p>";
        echo "<p><strong>Path ID:</strong> {$pathid}</p>";
        echo "<p><strong>User ID:</strong> {$USER->id}</p>";
        echo "<p><strong>Estilo:</strong> {$style}</p>";
        
        if ($progress) {
            // Verificar el paso actual
            $current_step_check = $DB->get_record('learningpath_steps', ['id' => $progress->current_stepid]);
            echo "<p><strong>Paso actual existe:</strong> " . ($current_step_check ? "SÍ" : "NO") . "</p>";
            
            if ($current_step_check) {
                echo "<p><strong>Paso detalles:</strong> Recurso ID {$current_step_check->resourceid}, Test: {$current_step_check->istest}, Step Number: {$current_step_check->stepnumber}</p>";
                
                // Verificar el recurso del paso
                $resource_check = $DB->get_record('learningstylesurvey_resources', ['id' => $current_step_check->resourceid]);
                echo "<p><strong>Recurso existe:</strong> " . ($resource_check ? "SÍ (Estilo: {$resource_check->style}, Tema: {$resource_check->tema})" : "NO") . "</p>";
            }
        }
        echo "</div>";
    }
    
    if ($progress && $progress->current_stepid) {
        // Si hay progreso, mostrar el paso actual del usuario
        $step = $DB->get_record('learningpath_steps', ['id' => $progress->current_stepid]);
        
        // Debug temporal
        if ($debug_info) {
            echo "<div class='alert alert-info'>";
            echo "<h4>Debug - Verificando paso actual:</h4>";
            echo "<p><strong>Step encontrado:</strong> " . ($step ? "SÍ" : "NO") . "</p>";
            if ($step) {
                echo "<p><strong>Es test:</strong> " . ($step->istest ? "SÍ" : "NO") . "</p>";
                echo "<p><strong>Resource ID:</strong> {$step->resourceid}</p>";
            }
            echo "</div>";
        }
        
        // Verificar que el step existe y coincide con el estilo del usuario
        if ($step && !$step->istest) {
            $resource_check = $DB->get_record('learningstylesurvey_resources', [
                'id' => $step->resourceid,
                'style' => $style
            ]);
            
            // Debug temporal
            if ($debug_info) {
                echo "<div class='alert alert-warning'>";
                echo "<h4>Debug - Validación de recurso:</h4>";
                echo "<p><strong>Recurso encontrado para estilo:</strong> " . ($resource_check ? "SÍ" : "NO") . "</p>";
                if ($resource_check) {
                    echo "<p><strong>Recurso estilo:</strong> {$resource_check->style}</p>";
                    echo "<p><strong>Recurso tema:</strong> {$resource_check->tema}</p>";
                }
                echo "</div>";
            }
            
            // Verificar que el tema no sea de refuerzo
            if ($resource_check) {
                $tema_check = $DB->get_record('learningstylesurvey_path_temas', [
                    'pathid' => $pathid,
                    'temaid' => $resource_check->tema,
                    'isrefuerzo' => 0  // Solo temas normales
                ]);
                
                // Debug temporal
                if ($debug_info) {
                    echo "<div class='alert alert-secondary'>";
                    echo "<h4>Debug - Validación de tema:</h4>";
                    echo "<p><strong>Tema válido (no refuerzo):</strong> " . ($tema_check ? "SÍ" : "NO") . "</p>";
                    echo "</div>";
                }
                
                if (!$tema_check) {
                    // Si el tema es de refuerzo, buscar el siguiente paso apropiado
                    $resource_check = null;
                }
            }
            
            if (!$resource_check) {
                // Si el paso actual no coincide con el estilo o es refuerzo, buscar el siguiente paso apropiado
                $step = $DB->get_record_sql("
                    SELECT s.*
                    FROM {learningpath_steps} s
                    JOIN {learningstylesurvey_resources} r ON s.resourceid = r.id
                    JOIN {learningstylesurvey_path_temas} pt ON pt.temaid = r.tema AND pt.pathid = s.pathid
                    WHERE s.pathid = ? AND r.style = ? AND s.istest = 0 AND s.stepnumber > ? AND pt.isrefuerzo = 0
                    ORDER BY s.stepnumber ASC
                    LIMIT 1
                ", [$pathid, $style, $step->stepnumber]);
            }
        }
    } else {
        // Si no hay progreso, crear uno y mostrar el primer recurso (excluyendo temas de refuerzo)
        $step = $DB->get_record_sql("
            SELECT s.*
            FROM {learningpath_steps} s
            JOIN {learningstylesurvey_resources} r ON s.resourceid = r.id
            JOIN {learningstylesurvey_path_temas} pt ON pt.temaid = r.tema AND pt.pathid = s.pathid
            WHERE s.pathid = ? AND r.style = ? AND s.istest = 0 AND pt.isrefuerzo = 0
            ORDER BY s.stepnumber ASC
            LIMIT 1
        ", [$pathid, $style]);
        
        if ($step) {
            // Crear registro de progreso
            $new_progress = (object)[
                'userid' => $USER->id,
                'pathid' => $pathid,
                'current_stepid' => $step->id,
                'status' => 'inprogress',
                'timemodified' => time()
            ];
            $DB->insert_record('learningstylesurvey_user_progress', $new_progress);
        }
    }
    
    // Debug temporal - validación final del paso
    if ($debug_info) {
        echo "<div class='alert alert-danger'>";
        echo "<h4>Debug - Validación final del paso:</h4>";
        echo "<p><strong>¿Paso válido encontrado?:</strong> " . ($step ? "SÍ (Step ID: {$step->id})" : "NO") . "</p>";
        if ($step) {
            echo "<p><strong>Resource ID del paso:</strong> {$step->resourceid}</p>";
            echo "<p><strong>Es test:</strong> " . ($step->istest ? "SÍ" : "NO") . "</p>";
        }
        echo "</div>";
    }
    
    if ($step) {
        if ($step->istest) {
            // Si es un test, buscar en la tabla de quizzes
            $quiz = $DB->get_record('learningstylesurvey_quizzes', ['id' => $step->resourceid]);
            
            // Debug temporal
            if ($debug_info) {
                echo "<div class='alert alert-success'>";
                echo "<h4>Debug - Quiz encontrado:</h4>";
                echo "<p><strong>Quiz encontrado:</strong> " . ($quiz ? "SÍ" : "NO") . "</p>";
                if ($quiz) {
                    echo "<p><strong>Quiz nombre:</strong> {$quiz->name}</p>";
                    echo "<p><strong>Quiz ID:</strong> {$quiz->id}</p>";
                }
                echo "</div>";
            }
            
            if ($quiz) {
                // Verificar si ya fue aprobado
                $quiz_result = $DB->get_record_sql("
                    SELECT * FROM {learningstylesurvey_quiz_results}
                    WHERE userid = ? AND quizid = ? AND score >= 70
                    ORDER BY timecompleted DESC LIMIT 1
                ", [$USER->id, $quiz->id]);
                
                if ($quiz_result) {
                    // Examen ya aprobado, avanzar al siguiente paso
                    $next_step = $DB->get_record_sql("
                        SELECT s.*
                        FROM {learningpath_steps} s
                        JOIN {learningstylesurvey_resources} r ON s.resourceid = r.id
                        JOIN {learningstylesurvey_path_temas} pt ON pt.temaid = r.tema AND pt.pathid = s.pathid
                        WHERE s.pathid = ? AND r.style = ? AND s.istest = 0 AND s.stepnumber > ? AND pt.isrefuerzo = 0
                        ORDER BY s.stepnumber ASC
                        LIMIT 1
                    ", [$pathid, $style, $step->stepnumber]);
                    
                    if ($next_step) {
                        // Actualizar progreso al siguiente paso
                        $progress->current_stepid = $next_step->id;
                        $progress->timemodified = time();
                        $DB->update_record('learningstylesurvey_user_progress', $progress);
                        
                        // Usar el siguiente paso como paso actual
                        $step = $next_step;
                    } else {
                        $step = null; // No hay más pasos, ir a finalización
                    }
                } else {
                    // Mostrar el examen
                    echo "<div class='alert alert-info'>Tienes un examen pendiente: <strong>" . format_string($quiz->name) . "</strong></div>";
                    $quizurl = new moodle_url('/mod/learningstylesurvey/quiz/responder_quiz.php', [
                        'id' => $quiz->id,
                        'courseid' => $courseid,
                        'embedded' => 1,
                        'cmid' => $cmid
                    ]);
                    echo "<a href='{$quizurl}' class='btn btn-primary btn-lg'>📝 Realizar examen</a>";
                    echo "</div>";
                    echo $OUTPUT->footer();
                    exit;
                }
            }
        }
        
        // Si llegamos aquí y aún tenemos un step, debe ser un recurso
        if ($step && !$step->istest) {
            $resource = $DB->get_record('learningstylesurvey_resources', ['id' => $step->resourceid]);
        } else {
            $resource = null;
        }
        
        // Debug temporal
        if ($debug_info) {
            echo "<div class='alert alert-success'>";
            echo "<h4>Debug - Recurso final:</h4>";
            echo "<p><strong>Recurso encontrado:</strong> " . ($resource ? "SÍ" : "NO") . "</p>";
            if ($resource) {
                echo "<p><strong>Recurso estilo:</strong> {$resource->style}</p>";
                echo "<p><strong>Recurso tema:</strong> {$resource->tema}</p>";
                echo "<p><strong>Archivo:</strong> {$resource->filename}</p>";
            }
            echo "</div>";
        }
        
        if ($resource) {
            // Mostrar título del tema actual
            $tema_actual = $DB->get_record('learningstylesurvey_temas', ['id' => $resource->tema]);
            if ($tema_actual) {
                echo "<div style='background:#e7f3ff; border-left:4px solid #007bff; padding:15px; margin-bottom:20px; border-radius:5px;'>";
                echo "<h3 style='margin:0; color:#0056b3;'>📚 " . format_string($tema_actual->tema) . "</h3>";
                echo "</div>";
            }
            
            mostrar_recurso($resource);
            
            // Botón de avance manual
            echo "<form method='POST' action='siguiente.php'>
                    <input type='hidden' name='courseid' value='{$courseid}'>
                    <input type='hidden' name='pathid' value='{$pathid}'>
                    <input type='hidden' name='stepid' value='{$step->id}'>
                    <input type='hidden' name='cmid' value='{$cmid}'>
                    <button type='submit' class='btn btn-success' style='margin-top:15px;'>Continuar</button>
                  </form>";
        }
    } else {
        // No hay más recursos - verificar si hay examen pendiente
        $pending_quiz = $DB->get_record_sql("
            SELECT s.*
            FROM {learningpath_steps} s
            WHERE s.pathid = ? AND s.istest = 1
            ORDER BY s.stepnumber ASC
            LIMIT 1
        ", [$pathid]);
        
        if ($pending_quiz) {
            // Verificar si este examen ya fue aprobado
            $quiz_result = $DB->get_record_sql("
                SELECT qr.* FROM {learningstylesurvey_quiz_results} qr
                WHERE qr.userid = ? AND qr.quizid = ? AND qr.score >= 70
                ORDER BY qr.timecompleted DESC LIMIT 1
            ", [$USER->id, $pending_quiz->resourceid]);
            
            if (!$quiz_result) {
                echo "<div class='alert alert-warning' style='text-align:center; padding:25px;'>";
                echo "<h4>📚 ¡Has completado todos los recursos del tema!</h4>";
                echo "<p>Ya has estudiado todo el material disponible para tu estilo de aprendizaje.</p>";
                echo "<p><strong>💡 Ahora es momento de evaluar lo aprendido.</strong></p>";
                echo "<p>Haz clic en el botón de abajo para realizar el examen.</p>";
                echo "</div>";
            } else {
                echo "<div class='alert alert-success' style='text-align:center; padding:25px;'>";
                echo "<h4>🎉 ¡Felicitaciones!</h4>";
                echo "<p>Has completado exitosamente toda la ruta de aprendizaje.</p>";
                echo "<p>✅ Examen aprobado con {$quiz_result->score}%</p>";
                if ($cmid) {
                    $menuurl = new moodle_url('/mod/learningstylesurvey/view.php', ['id'=>$cmid]);
                    echo "<a href='{$menuurl}' class='btn btn-primary btn-lg' style='margin-top:10px;'>Regresar al menú principal</a>";
                }
                echo "</div>";
            }
        } else {
            // Debug temporal - comentar cuando funcione
            if ($debug_info) {
                echo "<div class='alert alert-danger'>";
                echo "<h4>Debug - No hay recursos disponibles:</h4>";
                echo "<p><strong>Path ID:</strong> {$pathid}</p>";
                echo "<p><strong>Estilo:</strong> {$style}</p>";
                
                // Verificar cuántos pasos hay en total para este estilo
                $total_steps = $DB->get_records_sql("
                    SELECT s.*, r.style, r.tema
                    FROM {learningpath_steps} s
                    JOIN {learningstylesurvey_resources} r ON s.resourceid = r.id
                    WHERE s.pathid = ? AND r.style = ? AND s.istest = 0
                    ORDER BY s.stepnumber ASC
                ", [$pathid, $style]);
                
                echo "<p><strong>Pasos totales para tu estilo:</strong> " . count($total_steps) . "</p>";
                
                if ($total_steps) {
                    echo "<ul>";
                    foreach ($total_steps as $ts) {
                        echo "<li>Step {$ts->stepnumber}: Recurso {$ts->resourceid}, Tema {$ts->tema}</li>";
                    }
                    echo "</ul>";
                }
                echo "</div>";
            }
            
            echo "<div class='alert alert-info' style='text-align:center;'>";
            echo "<h4>📖 Ruta en progreso</h4>";
            echo "<p>No hay más recursos disponibles en este momento para tu estilo de aprendizaje.</p>";
            echo "</div>";
        }
    }
}

// Buscar el primer examen programado en la ruta que NO haya sido aprobado
$quizstep = $DB->get_record_sql("
    SELECT s.*
    FROM {learningpath_steps} s
    WHERE s.pathid = ? AND s.istest = 1
    ORDER BY s.stepnumber ASC
    LIMIT 1
", [$pathid]);

if ($quizstep && $cmid) {
    // Verificar si este examen ya fue aprobado
    $quiz_result = $DB->get_record_sql("
        SELECT qr.* FROM {learningstylesurvey_quiz_results} qr
        WHERE qr.userid = ? AND qr.quizid = ? AND qr.score >= 70
        ORDER BY qr.timecompleted DESC LIMIT 1
    ", [$USER->id, $quizstep->resourceid]);
    
    // Solo mostrar el examen si NO ha sido aprobado
    if (!$quiz_result) {
        echo "<div style='margin-top:30px;'>";
        echo "<h3>Examen programado</h3>";
        echo html_writer::link(
            new moodle_url('/mod/learningstylesurvey/quiz/responder_quiz.php', [
                'id' => $quizstep->resourceid,
                'courseid' => $courseid,
                'embedded' => 0,
                'cmid' => $cmid
            ]),
            'Ir al examen',
            ['class'=>'btn btn-primary']
        );
        echo "</div>";
    }
}

// Botón regresar al menú
if ($cmid) {
    $menuurl = new moodle_url('/mod/learningstylesurvey/view.php', ['id'=>$cmid]);
    echo "<div style='margin-top:30px;'><a href='{$menuurl}' class='btn btn-secondary'>Regresar al menú</a></div>";
}

echo "</div>";
echo $OUTPUT->footer();
?>
