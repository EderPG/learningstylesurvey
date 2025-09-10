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

/**
 * Asegura que el directorio de archivos del curso existe
 */
function learningstylesurvey_ensure_upload_directory($courseid) {
    global $CFG;
    
    $upload_dir = $CFG->dataroot . '/learningstylesurvey/' . $courseid . '/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    return $upload_dir;
}

/**
 * Migra archivos existentes desde el directorio antiguo al nuevo
 */
function learningstylesurvey_migrate_files($courseid) {
    global $CFG;
    
    $old_dir = $CFG->dirroot . '/mod/learningstylesurvey/uploads/';
    $new_dir = $CFG->dataroot . '/learningstylesurvey/' . $courseid . '/';
    
    if (is_dir($old_dir)) {
        learningstylesurvey_ensure_upload_directory($courseid);
        
        $files = glob($old_dir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                $filename = basename($file);
                $new_path = $new_dir . $filename;
                if (!file_exists($new_path)) {
                    copy($file, $new_path);
                }
            }
        }
    }
}
