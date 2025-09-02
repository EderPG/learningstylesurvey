<?php
require_once('../../config.php');
require_login();

$courseid = required_param('courseid', PARAM_INT);
$quizid = optional_param('quizid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

$context = context_course::instance($courseid);
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/mod/learningstylesurvey/manage_quiz.php', ['courseid' => $courseid]));
$PAGE->set_title('Gestionar Examen');
$PAGE->set_heading('Gestionar Examen');

global $DB, $OUTPUT;

// Eliminar examen
if ($action === 'delete' && $quizid) {
    // Eliminar preguntas y opciones asociadas
    $questions = $DB->get_records('learningstylesurvey_questions', ['quizid' => $quizid]);
    foreach ($questions as $q) {
        $DB->delete_records('learningstylesurvey_options', ['questionid' => $q->id]);
    }
    $DB->delete_records('learningstylesurvey_questions', ['quizid' => $quizid]);
    $DB->delete_records('learningstylesurvey_quiz_results', ['quizid' => $quizid]);
    $DB->delete_records('learningstylesurvey_quizzes', ['id' => $quizid]);
    redirect(new moodle_url('/mod/learningstylesurvey/manage_quiz.php', ['courseid' => $courseid]), 'Examen eliminado correctamente.', 1);
}

// Editar examen
if ($action === 'edit' && $quizid) {
    $quiz = $DB->get_record('learningstylesurvey_quizzes', ['id' => $quizid, 'courseid' => $courseid], '*', MUST_EXIST);
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
        redirect(new moodle_url('/mod/learningstylesurvey/manage_quiz.php', ['courseid' => $courseid]), 'Examen editado correctamente.', 1);
    }

    // Mostrar formulario de edición
    echo $OUTPUT->header();
    echo $OUTPUT->heading('Editar Examen');
    // Botón regresar igual que en la vista principal
    $cmid = 0;
    $instances = get_fast_modinfo($courseid)->get_instances_of('learningstylesurvey');
    if ($instances) {
        $firstcm = reset($instances);
        $cmid = $firstcm->id;
    }
    if ($cmid) {
        $viewurl = new moodle_url('/mod/learningstylesurvey/view.php', ['id' => $cmid]);
        echo '<a href="' . $viewurl->out() . '" class="btn btn-dark" style="margin-bottom:20px;">Regresar al menú</a>';
    } else {
        echo '<a href="' . new moodle_url('/course/view.php', ['id' => $courseid]) . '" class="btn btn-dark" style="margin-bottom:20px;">Regresar al curso</a>';
    }
    echo '<form method="post" id="editquizform">';
    echo '<label>Nombre del examen:</label><br>';
    echo '<input type="text" name="name" value="' . s($quiz->name) . '" required><br><br>';
    echo '<div id="questions">';
    foreach ($questions as $q) {
        echo '<div class="question-block" style="margin-bottom:20px; padding:10px; border:1px solid #ccc; border-radius:8px;">';
        echo '<label>Pregunta:</label><br>';
    echo '<input type="text" name="question_' . $q->id . '" value="' . s($q->questiontext) . '" required><br>';
        echo '<label>Opciones:</label><br>';
        $options = $DB->get_records('learningstylesurvey_options', ['questionid' => $q->id]);
        $optionIndex = 0;
        foreach ($options as $opt) {
            echo '<input type="text" name="option_' . $q->id . '_' . $optionIndex . '" value="' . s($opt->optiontext) . '" required> ';
            echo '<input type="radio" name="correct_' . $q->id . '" value="' . $optionIndex . '"' . ($q->correctanswer == $optionIndex ? ' checked' : '') . '> Correcta<br>';
            $optionIndex++;
        }
        echo '<button type="button" class="btn btn-danger" onclick="eliminarPregunta(this)">Eliminar pregunta</button>';
        echo '</div>';
    }
    echo '</div>';
    echo '<button type="button" class="btn btn-info" onclick="agregarPregunta()">Agregar nueva pregunta</button>';
    echo '<br><br><button type="submit" name="savequiz">Guardar cambios</button>';
    echo '</form>';

    ?>
    <script>
    function agregarPregunta() {
        var container = document.getElementById("questions");
        var num = document.querySelectorAll(".question-block").length;
        var div = document.createElement("div");
        div.className = "question-block";
        div.style = "margin-bottom:20px; padding:10px; border:1px solid #ccc; border-radius:8px;";
        div.innerHTML = `
            <label>Pregunta:</label><br>
            <input type="text" name="new_question_${num}" required><br>
            <label>Opciones:</label><br>
            <input type="text" name="new_option_${num}_0" required> <input type="radio" name="new_correct_${num}" value="0" checked> Correcta<br>
            <input type="text" name="new_option_${num}_1" required> <input type="radio" name="new_correct_${num}" value="1"> Correcta<br>
            <input type="text" name="new_option_${num}_2"> <input type="radio" name="new_correct_${num}" value="2"> Correcta<br>
            <input type="text" name="new_option_${num}_3"> <input type="radio" name="new_correct_${num}" value="3"> Correcta<br>
            <button type="button" class="btn btn-danger" onclick="eliminarPregunta(this)">Eliminar pregunta</button>
        `;
        container.appendChild(div);
    }
    function eliminarPregunta(btn) {
        btn.parentElement.remove();
    }
    </script>
    <?php
    echo $OUTPUT->footer();
    exit;
}

// Listar exámenes
$quizzes = $DB->get_records('learningstylesurvey_quizzes', ['courseid' => $courseid]);
echo $OUTPUT->header();
echo $OUTPUT->heading('Exámenes del curso');

// Obtener el cmid del módulo learningstylesurvey
$cmid = 0;
$instances = get_fast_modinfo($courseid)->get_instances_of('learningstylesurvey');
if ($instances) {
    $firstcm = reset($instances);
    $cmid = $firstcm->id;
}

if ($cmid) {
    $viewurl = new moodle_url('/mod/learningstylesurvey/view.php', ['id' => $cmid]);
    echo '<a href="' . $viewurl->out() . '" class="btn btn-dark" style="margin-bottom:20px;">Regresar al menú</a>';
} else {
    echo '<a href="' . new moodle_url('/course/view.php', ['id' => $courseid]) . '" class="btn btn-dark" style="margin-bottom:20px;">Regresar al curso</a>';
}
echo '<ul style="list-style:none; padding:0;">';
foreach ($quizzes as $quiz) {
    echo '<li style="margin-bottom:20px; padding:10px; border:1px solid #ddd; border-radius:8px; background:#f9f9f9;">';
    echo '<strong>' . format_string($quiz->name) . '</strong><br>';
    echo '<a href="?quizid=' . $quiz->id . '&courseid=' . $courseid . '&action=edit" class="btn btn-primary">Editar</a> ';
    echo '<a href="?quizid=' . $quiz->id . '&courseid=' . $courseid . '&action=delete" class="btn btn-danger" onclick="return confirm(\'¿Seguro que deseas eliminar este examen?\')">Eliminar</a>';
    echo '</li>';
}
echo '</ul>';
echo $OUTPUT->footer();
