<?php
defined('MOODLE_INTERNAL') || die();

function learningstylesurvey_get_responses($surveyid) {
    global $DB;
    return $DB->get_records('learningstylesurvey_responses', ['surveyid' => $surveyid]);
}