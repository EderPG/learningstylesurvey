<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/learningstylesurvey/lib.php');

class mod_learningstylesurvey_mod_form extends moodleform_mod {

    function definition() {
        $mform = $this->_form;

        // Nombre del modulo
        $mform->addElement('text', 'name', get_string('learningstylesurveyname', 'learningstylesurvey'), array('size' => '64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        // Descripcion
        $this->standard_intro_elements();

        // Tiempo disponible
        $this->standard_coursemodule_elements();

        // Botones
        $this->add_action_buttons();
    }
}
