<?php
require('../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$course = get_course($courseid);

require_login($course);
$context = context_course::instance($course->id);
require_capability('moodle/course:update', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/mod/learningstylesurvey/temas.php', ['courseid' => $courseid]));
$PAGE->set_title('Temas del curso');
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();

// Guardar tema nuevo
if (optional_param('submit', false, PARAM_BOOL)) {
    require_sesskey();
    $tema = trim(required_param('tema', PARAM_TEXT));
    // También recuperamos courseid por POST para validar y evitar problemas
    $postcourseid = required_param('courseid', PARAM_INT);

    if ($tema !== '' && $postcourseid == $courseid) {
        $record = (object)[
            'courseid'    => $courseid,
            'tema'        => $tema,
            'timecreated' => time()
        ];
        $DB->insert_record('learningstylesurvey_temas', $record);
        redirect(new moodle_url($PAGE->url, ['courseid' => $courseid]));
    } else {
        echo $OUTPUT->notification('Error: Datos del formulario inválidos.', 'notifyproblem');
    }
}

// Formulario para agregar un tema
echo html_writer::tag('h3', 'Agregar tema');
echo html_writer::start_tag('form', [
    'method' => 'post',
    'action' => $PAGE->url->out(false)
]);

echo html_writer::empty_tag('input', [
    'type'  => 'hidden',
    'name'  => 'sesskey',
    'value' => sesskey()
]);

echo html_writer::empty_tag('input', [
    'type'  => 'hidden',
    'name'  => 'courseid',
    'value' => $courseid
]);

echo html_writer::empty_tag('input', [
    'type'        => 'text',
    'name'        => 'tema',
    'placeholder' => 'Escribe el tema...',
    'required'    => true,
    'size'        => 50
]);

echo html_writer::empty_tag('input', [
    'type'  => 'submit',
    'name'  => 'submit',
    'value' => 'Guardar',
    'class' => 'btn btn-primary ml-2'
]);
echo html_writer::end_tag('form');

echo html_writer::tag('hr', '');

// Mostrar lista de temas existentes
$temas = $DB->get_records('learningstylesurvey_temas', ['courseid' => $courseid], 'timecreated DESC');
echo html_writer::tag('h3', 'Temas registrados');

if ($temas) {
    echo html_writer::start_tag('ul');
    foreach ($temas as $t) {
        echo html_writer::tag('li', format_string($t->tema));
    }
    echo html_writer::end_tag('ul');
} else {
    echo html_writer::tag('p', 'No hay temas registrados aún.');
}

echo html_writer::tag('br', '');
echo html_writer::link(
    new moodle_url('/course/view.php', ['id' => $courseid]),
    'Regresar al curso',
    ['class' => 'btn btn-secondary']
);

echo $OUTPUT->footer();
