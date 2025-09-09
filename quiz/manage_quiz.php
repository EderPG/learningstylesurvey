<?php
require_once('../../../config.php');
require_login();

$courseid = required_param('courseid', PARAM_INT);
$cmid = optional_param('cmid', 0, PARAM_INT); // ID de la instancia específica
$quizid = optional_param('quizid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

$context = context_course::instance($courseid);
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/mod/learningstylesurvey/quiz/manage_quiz.php', ['courseid' => $courseid, 'cmid' => $cmid]));
$PAGE->set_title('Gestionar Examen');
$PAGE->set_heading('Gestionar Examen');

global $DB, $OUTPUT;

// Determinar el cmid correcto una sola vez
if ($cmid > 0) {
    $targetcmid = $cmid;
} else {
    $targetcmid = 0;
    $instances = get_fast_modinfo($courseid)->get_instances_of('learningstylesurvey');
    if ($instances) {
        $firstcm = reset($instances);
        $targetcmid = $firstcm->id;
    }
}

// Eliminar examen
if ($action === 'delete' && $quizid) {
    // ✅ Verificar que el examen pertenece al usuario actual
    $quiz = $DB->get_record('learningstylesurvey_quizzes', ['id' => $quizid, 'courseid' => $courseid, 'userid' => $USER->id]);
    if (!$quiz) {
        print_error('No tienes permisos para eliminar este examen.');
    }
    
    // Eliminar preguntas y opciones asociadas
    $questions = $DB->get_records('learningstylesurvey_questions', ['quizid' => $quizid]);
    foreach ($questions as $q) {
        $DB->delete_records('learningstylesurvey_options', ['questionid' => $q->id]);
    }
    $DB->delete_records('learningstylesurvey_questions', ['quizid' => $quizid]);
    $DB->delete_records('learningstylesurvey_quiz_results', ['quizid' => $quizid]);
    $DB->delete_records('learningstylesurvey_quizzes', ['id' => $quizid]);
    redirect(new moodle_url('/mod/learningstylesurvey/quiz/manage_quiz.php', ['courseid' => $courseid]), 'Examen eliminado correctamente.', 1);
}

// Editar examen
if ($action === 'edit' && $quizid) {
    $quiz = $DB->get_record('learningstylesurvey_quizzes', ['id' => $quizid, 'courseid' => $courseid, 'userid' => $USER->id], '*', MUST_EXIST);
    $questions = $DB->get_records('learningstylesurvey_questions', ['quizid' => $quizid]);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['savequiz'])) {
        $quiz->name = required_param('name', PARAM_TEXT);
        $DB->update_record('learningstylesurvey_quizzes', $quiz);

        // Editar preguntas existentes y opciones
        $preguntas_actualizadas = [];
        foreach ($questions as $q) {
            $qid = $q->id;
            // Si la pregunta fue eliminada en el frontend, no estará en POST
            if (!isset($_POST['question_' . $qid])) {
                // Eliminar pregunta y sus opciones
                $DB->delete_records('learningstylesurvey_options', ['questionid' => $qid]);
                $DB->delete_records('learningstylesurvey_questions', ['id' => $qid]);
                continue;
            }
            $q->questiontext = required_param('question_' . $qid, PARAM_TEXT);
            $q->correctanswer = required_param('correct_' . $qid, PARAM_INT);
            $DB->update_record('learningstylesurvey_questions', $q);
            $preguntas_actualizadas[] = $qid;
            // Editar opciones
            $options = array_values($DB->get_records('learningstylesurvey_options', ['questionid' => $qid]));
            for ($idx = 0; $idx < count($options); $idx++) {
                $opt = $options[$idx];
                $fieldname = 'option_' . $qid . '_' . $idx;
                if (isset($_POST[$fieldname]) && trim($_POST[$fieldname]) !== '') {
                    $opt->optiontext = required_param($fieldname, PARAM_TEXT);
                    $DB->update_record('learningstylesurvey_options', $opt);
                } else {
                    // Si el campo no existe o está vacío, eliminar la opción
                    $DB->delete_records('learningstylesurvey_options', ['id' => $opt->id]);
                }
            }
        }

        // Agregar nuevas preguntas
        foreach ($_POST as $key => $value) {
            if (preg_match('/^new_question_(\d+)$/', $key, $matches)) {
                $num = $matches[1];
                $questiontext = required_param($key, PARAM_TEXT);
                $correct = optional_param('new_correct_' . $num, 0, PARAM_INT);
                $newq = new stdClass();
                $newq->quizid = $quizid;
                $newq->questiontext = $questiontext;
                $newq->correctanswer = $correct;
                $newq->timecreated = time();
                $newq->timemodified = time();
                $newqid = $DB->insert_record('learningstylesurvey_questions', $newq);
                // Opciones
                for ($i = 0; $i < 4; $i++) {
                    $optkey = 'new_option_' . $num . '_' . $i;
                    if (isset($_POST[$optkey]) && trim($_POST[$optkey]) !== '') {
                        $opt = new stdClass();
                        $opt->questionid = $newqid;
                        $opt->optiontext = required_param($optkey, PARAM_TEXT);
                        $DB->insert_record('learningstylesurvey_options', $opt);
                    }
                }
            }
        }
        redirect(new moodle_url('/mod/learningstylesurvey/quiz/manage_quiz.php', ['courseid' => $courseid]), 'Examen editado correctamente.', 1);
    }

    // Mostrar formulario de edición
    echo $OUTPUT->header();
    echo $OUTPUT->heading('Editar Examen');
    // Botón regresar igual que en la vista principal
    if ($targetcmid) {
        $viewurl = new moodle_url('/mod/learningstylesurvey/view.php', ['id' => $targetcmid]);
        echo '<a href="' . $viewurl->out() . '" class="btn btn-dark" style="margin-bottom:20px;">Regresar al menú</a>';
    } else {
        echo '<a href="' . new moodle_url('/course/view.php', ['id' => $courseid]) . '" class="btn btn-dark" style="margin-bottom:20px;">Regresar al curso</a>';
    }
    ?>
    <style>
        .quiz-form-container {
            max-width: 1000px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        .form-group {
            margin-bottom: 25px;
        }
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: block;
            font-size: 16px;
        }
        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        .form-input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        .question-card {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            transition: all 0.3s ease;
        }
        .question-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-color: #007bff;
        }
        .option-container {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            margin: 8px 0;
            border-radius: 8px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .option-container.correct {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border-color: #28a745;
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.2);
        }
        .option-container:not(.correct) {
            background: #ffffff;
            border-color: #e1e5e9;
        }
        .option-container:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .option-input {
            flex: 1;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            margin-right: 15px;
            font-size: 15px;
        }
        .correct-radio {
            margin-right: 10px;
            transform: scale(1.4);
            accent-color: #28a745;
        }
        .correct-label {
            font-weight: 600;
            color: #28a745;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .correct-label .checkmark {
            font-size: 18px;
            color: #28a745;
        }
        .btn-modern {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-danger-modern {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }
        .btn-danger-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }
        .btn-success-modern {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        .btn-success-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }
        .btn-info-modern {
            background: linear-gradient(135deg, #17a2b8 0%, #20c997 100%);
            color: white;
        }
        .btn-info-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(23, 162, 184, 0.3);
        }
        .question-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .question-icon {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-weight: bold;
        }
    </style>
    
    <div class="quiz-form-container">
        <form method="post" id="editquizform">
            <div class="form-group">
                <label class="form-label">📝 Nombre del examen:</label>
                <input type="text" name="name" value="<?php echo s($quiz->name); ?>" required class="form-input">
            </div>
            
            <div id="questions">
                <?php
                $questionNumber = 1;
                foreach ($questions as $q) {
                    echo '<div class="question-card">';
                    echo '<div class="question-header">';
                    echo '<div class="question-icon">' . $questionNumber . '</div>';
                    echo '<div style="flex: 1;">';
                    echo '<label class="form-label">Pregunta:</label>';
                    echo '<input type="text" name="question_' . $q->id . '" value="' . s($q->questiontext) . '" required class="form-input">';
                    echo '</div>';
                    echo '</div>';
                    
                    echo '<label class="form-label">📋 Opciones (Selecciona la respuesta correcta):</label>';
                    
                    $options = $DB->get_records('learningstylesurvey_options', ['questionid' => $q->id]);
                    $optionIndex = 0;
                    foreach ($options as $opt) {
                        // ✅ Verificación robusta para correctanswer (maneja tanto índice numérico como texto)
                        $isCorrect = false;
                        if (is_numeric($q->correctanswer)) {
                            // Nuevo formato: índice numérico
                            $isCorrect = ((int)$q->correctanswer == $optionIndex);
                        } else {
                            // Formato antiguo: texto de la opción
                            $isCorrect = (trim($q->correctanswer) == trim($opt->optiontext));
                        }
                        $correctClass = $isCorrect ? 'correct' : '';
                        
                        echo '<div class="option-container ' . $correctClass . '">';
                        echo '<input type="text" name="option_' . $q->id . '_' . $optionIndex . '" value="' . s($opt->optiontext) . '" required class="option-input">';
                        echo '<input type="radio" name="correct_' . $q->id . '" value="' . $optionIndex . '"' . ($isCorrect ? ' checked' : '') . ' class="correct-radio">';
                        echo '<label class="correct-label">';
                        if ($isCorrect) {
                            echo '✅ Correcta';
                        } else {
                            echo 'Correcta';
                        }
                        echo '</label>';
                        echo '</div>';
                        $optionIndex++;
                    }
                    
                    echo '<div style="margin-top: 15px;">';
                    echo '<button type="button" class="btn-modern btn-danger-modern" onclick="eliminarPregunta(this)">';
                    echo '🗑️ Eliminar pregunta';
                    echo '</button>';
                    echo '</div>';
                    echo '</div>';
                    $questionNumber++;
                }
                ?>
            </div>
            
            <div style="margin: 25px 0;">
                <button type="button" class="btn-modern btn-info-modern" onclick="agregarPregunta()">
                    ➕ Agregar nueva pregunta
                </button>
            </div>
            
            <div style="margin-top: 30px; text-align: center;">
                <button type="submit" name="savequiz" class="btn-modern btn-success-modern" style="font-size: 16px; padding: 15px 30px;">
                    💾 Guardar cambios
                </button>
            </div>
        </form>
    </div>

    <script>
    let questionCounter = <?php echo count($questions); ?>;
    
    function agregarPregunta() {
        questionCounter++;
        var container = document.getElementById("questions");
        var div = document.createElement("div");
        div.className = "question-card";
        div.innerHTML = `
            <div class="question-header">
                <div class="question-icon">${questionCounter}</div>
                <div style="flex: 1;">
                    <label class="form-label">Pregunta:</label>
                    <input type="text" name="new_question_${questionCounter}" required class="form-input">
                </div>
            </div>
            <label class="form-label">📋 Opciones (Selecciona la respuesta correcta):</label>
            <div class="option-container">
                <input type="text" name="new_option_${questionCounter}_0" required class="option-input">
                <input type="radio" name="new_correct_${questionCounter}" value="0" checked class="correct-radio">
                <label class="correct-label">✅ Correcta</label>
            </div>
            <div class="option-container">
                <input type="text" name="new_option_${questionCounter}_1" required class="option-input">
                <input type="radio" name="new_correct_${questionCounter}" value="1" class="correct-radio">
                <label class="correct-label">Correcta</label>
            </div>
            <div class="option-container">
                <input type="text" name="new_option_${questionCounter}_2" class="option-input">
                <input type="radio" name="new_correct_${questionCounter}" value="2" class="correct-radio">
                <label class="correct-label">Correcta</label>
            </div>
            <div class="option-container">
                <input type="text" name="new_option_${questionCounter}_3" class="option-input">
                <input type="radio" name="new_correct_${questionCounter}" value="3" class="correct-radio">
                <label class="correct-label">Correcta</label>
            </div>
            <div style="margin-top: 15px;">
                <button type="button" class="btn-modern btn-danger-modern" onclick="eliminarPregunta(this)">
                    🗑️ Eliminar pregunta
                </button>
            </div>
        `;
        container.appendChild(div);
        
        // Agregar eventos a los nuevos radio buttons
        const radioButtons = div.querySelectorAll('input[type="radio"]');
        radioButtons.forEach(radio => {
            radio.addEventListener('change', updateCorrectAnswerDisplay);
        });
    }
    
    function eliminarPregunta(btn) {
        btn.closest('.question-card').remove();
        updateQuestionNumbers();
    }
    
    function updateQuestionNumbers() {
        const questions = document.querySelectorAll('.question-card');
        questions.forEach((question, index) => {
            const icon = question.querySelector('.question-icon');
            if (icon) {
                icon.textContent = index + 1;
            }
        });
        questionCounter = questions.length;
    }
    
    function updateCorrectAnswerDisplay() {
        // Actualizar la visualización cuando se cambie la respuesta correcta
        const container = this.closest('.question-card');
        const options = container.querySelectorAll('.option-container');
        const radios = container.querySelectorAll('input[type="radio"]');
        
        options.forEach((option, index) => {
            const radio = radios[index];
            const label = option.querySelector('.correct-label');
            
            if (radio.checked) {
                option.classList.add('correct');
                label.textContent = '✅ Correcta';
            } else {
                option.classList.remove('correct');
                label.textContent = 'Correcta';
            }
        });
    }
    
    // Agregar eventos a todos los radio buttons existentes
    document.addEventListener('DOMContentLoaded', function() {
        const allRadios = document.querySelectorAll('input[type="radio"]');
        allRadios.forEach(radio => {
            radio.addEventListener('change', updateCorrectAnswerDisplay);
        });
    });
    </script>
    <?php
    echo $OUTPUT->footer();
    exit;
}

// Listar exámenes - ✅ Filtrar por usuario y curso
$quizzes = $DB->get_records('learningstylesurvey_quizzes', ['courseid' => $courseid, 'userid' => $USER->id]);
echo $OUTPUT->header();

?>
<style>
    .quiz-list-container {
        max-width: 1000px;
        margin: 0 auto;
        padding: 20px;
    }
    .quiz-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        padding: 25px;
        margin-bottom: 20px;
        transition: all 0.3s ease;
        border-left: 5px solid #007bff;
    }
    .quiz-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
    }
    .quiz-title {
        font-size: 20px;
        font-weight: 600;
        color: #333;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .quiz-title .icon {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
    }
    .quiz-actions {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    .btn-action {
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
    }
    .btn-edit {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
    }
    .btn-edit:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        color: white;
        text-decoration: none;
    }
    .btn-delete {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
    }
    .btn-delete:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        color: white;
        text-decoration: none;
    }
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: #f8f9fa;
        border-radius: 12px;
        border: 2px dashed #dee2e6;
    }
    .empty-state .icon {
        font-size: 64px;
        color: #6c757d;
        margin-bottom: 20px;
    }
    .empty-state h3 {
        color: #6c757d;
        margin-bottom: 10px;
    }
    .empty-state p {
        color: #868e96;
    }
</style>

<div class="quiz-list-container">
    <?php
    echo $OUTPUT->heading('📋 Exámenes del curso', 2, 'main');

    // Usar el mismo targetcmid que arriba
    if ($targetcmid) {
        $viewurl = new moodle_url('/mod/learningstylesurvey/view.php', ['id' => $targetcmid]);
        echo '<a href="' . $viewurl->out() . '" class="btn btn-dark" style="margin-bottom:25px; display: inline-flex; align-items: center; gap: 8px;">⬅️ Regresar al menú</a>';
    } else {
        echo '<a href="' . new moodle_url('/course/view.php', ['id' => $courseid]) . '" class="btn btn-dark" style="margin-bottom:25px; display: inline-flex; align-items: center; gap: 8px;">⬅️ Regresar al curso</a>';
    }

    if (empty($quizzes)) {
        echo '<div class="empty-state">';
        echo '<div class="icon">📝</div>';
        echo '<h3>No hay exámenes creados</h3>';
        echo '<p>Aún no se han creado exámenes para este curso. Crea tu primer examen para comenzar.</p>';
        echo '</div>';
    } else {
        foreach ($quizzes as $quiz) {
            echo '<div class="quiz-card">';
            echo '<div class="quiz-title">';
            echo '<div class="icon">📝</div>';
            echo '<span>' . format_string($quiz->name) . '</span>';
            echo '</div>';
            echo '<div class="quiz-actions">';
            echo '<a href="?quizid=' . $quiz->id . '&courseid=' . $courseid . '&action=edit" class="btn-action btn-edit">✏️ Editar</a>';
            echo '<a href="?quizid=' . $quiz->id . '&courseid=' . $courseid . '&action=delete" class="btn-action btn-delete" onclick="return confirm(\'¿Seguro que deseas eliminar este examen?\')">🗑️ Eliminar</a>';
            echo '</div>';
            echo '</div>';
        }
    }
    ?>
</div>

<?php
echo $OUTPUT->footer();
