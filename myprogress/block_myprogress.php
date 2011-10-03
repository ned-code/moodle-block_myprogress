<?php

require_once($CFG->libdir.'/completionlib.php');

class block_myprogress extends block_list {
    function init() {
        $this->title = get_string('pluginname', 'block_myprogress');
    }

    function get_content() {
        global $CFG, $DB, $OUTPUT,$COURSE,$course;       

        if($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';
        $completedactivities=0;
        $incompletedactivities=0;
        $savedactivities=0;
        $notattemptedactivities=0;
        $waitingforgradeactivities=0;
        $savedactivities=0;

        $course = $this->page->course;
        require_once($CFG->dirroot.'/course/lib.php');

        $modinfo = get_fast_modinfo($course);       
        $modfullnames = array();
        $completion = new completion_info($course);
        $activities = $completion->get_activities();
        
        if($completion->is_enabled()){
        
        foreach($activities as $activity){
            $data = $completion->get_data($activity, true, $userid=0, null);
            $completionstate=$data->completionstate;
            if($completionstate ==5){
                $waitingforgradeactivities++;
                
            }
            
            elseif($completionstate ==0){
                $notattemptedactivities++;
                
            }
 
            elseif($completionstate ==1 || $completionstate ==2){
                $completedactivities++;
                
            }

            elseif($completionstate ==3){
                $incompletedactivities++;
            }
            else if($completionstate ==4){
                $savedactivities++;
            }
            else{
                
            }
        
        }                 
        $context = get_context_instance(CONTEXT_COURSE, $course->id);
        
        if(!has_capability('moodle/course:update',$context) && !is_guest($context)&&
                !has_capability('moodle/grade:viewall',$context) && !is_guest($context)){
             $this->content->items[] =  '<a href="' . $CFG->wwwroot . '/blocks/myprogress/listactivities.php?id=' . $course->id . '&show=completed' .
                        '&navlevel=top">' . $completedactivities . ' Completed</a>';
             $this->content->icons[] = '<img src="' . $CFG->wwwroot . '/blocks/myprogress/pix/completed.gif"
                                                    height="16" width="16" alt="">'; 
             $this->content->items[] = '<a href="' . $CFG->wwwroot . '/blocks/myprogress/listactivities.php?id=' . $course->id . '&show=incompleted' .
                        '&navlevel=top">' . $incompletedactivities . ' Incomplete</a>';
             $this->content->icons[] = '<img src="' . $CFG->wwwroot . '/blocks/myprogress/pix/incomplete.gif"
                                                    height="16" width="16" alt="">';
             $this->content->items[] = '<a href="' . $CFG->wwwroot . '/blocks/myprogress/listactivities.php?id=' . $course->id . '&show=saved' .
                        '&navlevel=top">' . $savedactivities . ' Saved</a>';
             $this->content->icons[] = '<img src="' . $CFG->wwwroot . '/blocks/myprogress/pix/saved.gif"
                                                    height="16" width="16" alt="">';
             $this->content->items[] = '<a href="' . $CFG->wwwroot . '/blocks/myprogress/listactivities.php?id=' . $course->id . '&show=notattempted' .
                        '&navlevel=top">' . $notattemptedactivities . ' Not Attempted</a>';
             $this->content->icons[] = '<img src="' . $CFG->wwwroot . '/blocks/myprogress/pix/notattempted.gif"
                                                    height="16" width="16" alt="">';
             $this->content->items[] = '<a href="' . $CFG->wwwroot . '/blocks/myprogress/listactivities.php?id=' . $course->id . '&show=waitingforgrade' .
                        '&navlevel=top">'. $waitingforgradeactivities . ' Waiting for grade</a>';
             $this->content->icons[] = '<img src="' . $CFG->wwwroot . '/blocks/myprogress/pix/unmarked.gif"
                                                    height="16" width="16" alt="">'; 
             return $this->content;
        }
        else{
             $this->content->items[] = "My progress block content is only shown to student";
             $this->content->icons[] = "";
        }
        
    
    }
    else{
        $this->content->items[] = "Activity tracking is not enabled";
        $this->content->icons[] = "";
    }
        
    }

    function applicable_formats() {
        return array('all' => false,'course-*' => true);
    }
}
