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
            'userid'      => $USER->id, // ✅ Agregar userid para filtrado
            'tema'        => $tema,
            'timecreated' => time()
        ];
        $DB->insert_record('learningstylesurvey_temas', $record);
        redirect(new moodle_url($PAGE->url, ['courseid' => $courseid]));
    } else {
        echo $OUTPUT->notification('Error: Datos del formulario inválidos.', 'notifyproblem');
    }
}

// Procesar eliminación de tema
$deleteid = optional_param('deleteid', 0, PARAM_INT);
if ($deleteid > 0) {
    require_sesskey();
    // ✅ Verifica que el tema pertenece al curso actual Y al usuario actual
    $tema = $DB->get_record('learningstylesurvey_temas', ['id' => $deleteid, 'courseid' => $courseid, 'userid' => $USER->id]);
    if ($tema) {
        $DB->delete_records('learningstylesurvey_temas', ['id' => $deleteid]);
        redirect(new moodle_url($PAGE->url, ['courseid' => $courseid]));
    } else {
        echo $OUTPUT->notification('No se pudo eliminar el tema.', 'notifyproblem');
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

// Mostrar lista de temas existentes - ✅ Solo los del usuario actual
$temas = $DB->get_records('learningstylesurvey_temas', ['courseid' => $courseid, 'userid' => $USER->id], 'timecreated DESC');
echo html_writer::tag('h3', 'Temas registrados');

if ($temas) {
    echo html_writer::start_tag('ul');
    foreach ($temas as $t) {
        $deleteurl = new moodle_url($PAGE->url, [
            'courseid' => $courseid,
            'deleteid' => $t->id,
            'sesskey'  => sesskey()
        ]);
        $temahtml = format_string($t->tema) . ' ';
        $temahtml .= html_writer::link($deleteurl, 'Eliminar', [
            'onclick' => "return confirm('¿Seguro que deseas eliminar este tema?')",
            'class' => 'btn btn-danger btn-sm ml-2'
        ]);
        echo html_writer::tag('li', $temahtml);
    }
    echo html_writer::end_tag('ul');
} else {
    echo html_writer::tag('p', 'No hay temas registrados aún.');
}

echo html_writer::tag('br', '');

// Obtener el cmid del módulo learningstylesurvey en este curso
$cms = get_fast_modinfo($courseid)->get_instances_of('learningstylesurvey');
$firstcm = reset($cms);
$cmid = $firstcm->id;

echo html_writer::link(
    new moodle_url('/mod/learningstylesurvey/view.php', ['id' => $cmid]),
    'Regresar al menu',
    ['class' => 'btn btn-secondary']
);

echo $OUTPUT->footer();
