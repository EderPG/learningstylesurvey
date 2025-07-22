<?php
function learningstylesurvey_save_strongest_style($userid, $style) {
    global $DB;
    $record = $DB->get_record('learningstylesurvey_userstyle', ['userid' => $userid]);
    if ($record) {
        $record->strongeststyle = $style;
        $DB->update_record('learningstylesurvey_userstyle', $record);
    } else {
        $DB->insert_record('learningstylesurvey_userstyle', ['userid' => $userid, 'strongeststyle' => $style]);
    }
}

function learningstylesurvey_add_instance($data, $mform) {
    global $DB;
    $data->timecreated = time();
    return $DB->insert_record('learningstylesurvey', $data);
}

function learningstylesurvey_update_instance($data, $mform) {
    global $DB;
    $data->timemodified = time();
    $data->id = $data->instance;
    return $DB->update_record('learningstylesurvey', $data);
}

function learningstylesurvey_delete_instance($id) {
    global $DB;
    if (!$record = $DB->get_record('learningstylesurvey', array('id' => $id))) {
        return false;
    }
    $DB->delete_records('learningstylesurvey', array('id' => $id));
    return true;
}
