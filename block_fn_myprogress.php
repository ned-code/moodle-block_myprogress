<?php

require_once($CFG->libdir . '/completionlib.php');
require_once('lib.php');

/**
 *
 * base class for block
 * @author     MoodleFn
 */
class block_fn_myprogress extends block_list {

    function init() {
        $this->title = get_string('blocktitle', 'block_fn_myprogress');
    }

    /**
     * Constrols the block title based on instance configuration
     *
     * @return bool
     */
    public function specialization() {
        global $course;

        /// Need the bigger course object.
        $this->course = $course;

        if (empty($this->config->title)) {
            $this->title = get_string('pluginname', 'block_fn_myprogress');
        } else {
            $this->title = $this->config->title;
        }
    }

    /**
     * Return the block content * 
     * @return block content
     * @todo Finish documenting this function
     */
    function get_content() {
        global $CFG, $DB, $OUTPUT, $COURSE, $course, $USER;

        if ($this->content !== NULL) {
            return $this->content;
        } 
         $context = get_context_instance(CONTEXT_COURSE, $COURSE->id); 
//         if (!has_capability('block/fn_myprogress:viewblock', $context) && is_site_admin($USER->id)) {
//            return $this->content;
//        } 
        if (isset($SESSION->completioncache)) {
            unset($SESSION->completioncache);
        }       

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';
        $completedactivities = 0;
        $incompletedactivities = 0;
        $savedactivities = 0;
        $notattemptedactivities = 0;
        $waitingforgradeactivities = 0;
        $savedactivities = 0;

        $course = $this->page->course;
        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/blocks/fn_myprogress/lib.php');

        $modinfo = get_fast_modinfo($course);
        $modfullnames = array();
        $completion = new completion_info($course);
        $activities = $completion->get_activities();
         if (!has_capability('block/fn_myprogress:viewblock', $context) && is_siteadmin($USER)) {
            return $this->content;
        } 

        if ($completion->is_enabled() && !empty($completion)) {

            foreach ($activities as $activity) {
                if (!$activity->visible) {
                    continue;
                }

                $data = $completion->get_data($activity, true, $userid = 0, null);
                $completionstate = $data->completionstate;
                if ($completionstate == 0) {
                    if (($activity->module == 1)
                            && ($activity->modname == 'assignment' || $activity->modname == 'assign')
                            && ($activity->completion == 2)
                            && assignment_status($activity, $USER->id)) {

                        //grab assignment status
                        $assignement_status = assignment_status($activity, $USER->id);
                        if (isset($assignement_status)) {
                            if ($assignement_status == 'saved') {
                                $savedactivities++;
                            } else if ($assignement_status == 'submitted') {
                                $waitingforgradeactivities++;
                            }
                        }
                    } else {
                        $notattemptedactivities++;
                    }
                } elseif ($completionstate == 1 || $completionstate == 2) {
                    $completedactivities++;
                } elseif ($completionstate == 3) {

                    if (($activity->module == 1)
                            && ($activity->modname = 'assignment')
                            && ($activity->completion == 2)
                            && assignment_status($activity, $USER->id)) {

                        //grab assignment status
                        $assignement_status = assignment_status($activity, $USER->id);
                        if (isset($assignement_status)) {
                            if ($assignement_status == 'saved') {
                                $savedactivities++;
                            } else if ($assignement_status == 'submitted') {
                                $waitingforgradeactivities++;
                            }
                        }
                    } else {
                        $incompletedactivities++;
                    }
                } else {
                    // do nothing   
                }
            }
                $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
                if (has_capability('block/fn_myprogress:viewblock', $context) && !is_siteadmin($USER)) {                                  
                    $this->content->items[] = '<a href="' . $CFG->wwwroot . '/blocks/fn_myprogress/listactivities.php?id=' . $course->id . '&show=completed' .
                            '&navlevel=top">' . $completedactivities . ' Completed</a>';
                    $this->content->icons[] = '<img src="' . $CFG->wwwroot . '/blocks/fn_myprogress/pix/completed.gif"
                                                        height="16" width="16" alt="">';
                    $this->content->items[] = '<a href="' . $CFG->wwwroot . '/blocks/fn_myprogress/listactivities.php?id=' . $course->id . '&show=incompleted' .
                            '&navlevel=top">' . $incompletedactivities . ' Incomplete</a>';
                    $this->content->icons[] = '<img src="' . $CFG->wwwroot . '/blocks/fn_myprogress/pix/incomplete.gif"
                                                        height="16" width="16" alt="">';
                    $this->content->items[] = '<a href="' . $CFG->wwwroot . '/blocks/fn_myprogress/listactivities.php?id=' . $course->id . '&show=saved' .
                            '&navlevel=top">' . $savedactivities . ' Saved</a>';
                    $this->content->icons[] = '<img src="' . $CFG->wwwroot . '/blocks/fn_myprogress/pix/saved.gif"
                                                        height="16" width="16" alt="">';
                    $this->content->items[] = '<a href="' . $CFG->wwwroot . '/blocks/fn_myprogress/listactivities.php?id=' . $course->id . '&show=notattempted' .
                            '&navlevel=top">' . $notattemptedactivities . ' Not Attempted</a>';
                    $this->content->icons[] = '<img src="' . $CFG->wwwroot . '/blocks/fn_myprogress/pix/notattempted.gif"
                                                        height="16" width="16" alt="">';
                    $this->content->items[] = '<a href="' . $CFG->wwwroot . '/blocks/fn_myprogress/listactivities.php?id=' . $course->id . '&show=waitingforgrade' .
                            '&navlevel=top">' . $waitingforgradeactivities . ' Waiting for grade</a>';
                    $this->content->icons[] = '<img src="' . $CFG->wwwroot . '/blocks/fn_myprogress/pix/unmarked.gif"
                                                        height="16" width="16" alt="">';                    
                }           
        } else {
            $this->content->items[] = "<p>Completion tracking is not enabled at the site level.You must turn on this feature
                                        on if you wish to use to use the Assignment Tracking System for this course </p>";
            $this->content->icons[] = '<img src="' . $CFG->wwwroot . '/blocks/fn_myprogress/pix/warning.gif"
                                                        height="16" width="16" alt="">';
        }
        return $this->content;  
    }

    function applicable_formats() {
        return array('all' => false, 'course-*' => true);
    }

}
