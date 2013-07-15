<?php

/**
 *To Know the assignement state saved or submiited
 *
 * @param mod object
 * @param userid
 * @return saved or submitted
 * @todo Finish documenting this function
 */ 

function assignment_status($mod, $userid, $resubmission = false) {
    global $CFG, $DB, $USER, $SESSION;
    require_once ($CFG->dirroot.'/mod/assignment/lib.php');    

    if(isset($SESSION->completioncache)){
        unset($SESSION->completioncache);
    }

    if ($mod->modname == 'assignment') {
        if  (!($assignment = $DB->get_record('assignment', array('id' => $mod->instance)))) {
            
            return false;   // Doesn't exist... wtf?
        }
        require_once ($CFG->dirroot.'/mod/assignment/type/'.$assignment->assignmenttype.'/assignment.class.php');
        $assignmentclass = "assignment_$assignment->assignmenttype";
        $assignmentinstance = new $assignmentclass($mod->id, $assignment, $mod);
    
        if (!($submission = $assignmentinstance->get_submission($userid)) || empty($submission->timemodified)) {
            return false;
        }
    
        switch ($assignment->assignmenttype) {      
            case "upload":          
                if($assignment->var4){ //if var4 enable then assignment can be saved                
                    if(!empty($submission->timemodified)
                            && (empty($submission->data2))
                            && (empty($submission->timemarked))){                  
                        return 'saved';
                        
                    }
                    else if(!empty($submission->timemodified)
                            && ($submission->data2='submitted')
                            && empty($submission->timemarked)){                
                        return 'submitted';                    
                    }
                    else if(!empty($submission->timemodified)
                            && ($submission->data2='submitted')
                            && ($submission->grade==-1)){
                        return 'submitted';
                        
                    }
                }
                else if(empty($submission->timemarked)){               
                    return 'submitted';                
                }            
                break;
            case "uploadsingle":            
                if(empty($submission->timemarked)){           
                     return 'submitted';                
                }            
                break;
            case "online":
                if(empty($submission->timemarked)){       
                     return 'submitted';                
                }             
                break;
            case "offline":           
                if(empty($submission->timemarked)){     
                     return 'submitted';                
                }
                break;
        }
    } else if ($mod->modname == 'assign') { 
        if  (!($assignment = $DB->get_record('assign', array('id' => $mod->instance)))) {
            return false; // Doesn't exist
        }
  
        if (!$submission = $DB->get_records('assign_submission', array('assignment'=>$assignment->id, 'userid'=>$userid), 'attemptnumber DESC', '*', 0, 1)) {
            return false;
        }else{
            $submission = reset($submission);
        }
        
        $attemptnumber = $submission->attemptnumber;
        
        if (($submission->status == 'reopened') && ($submission->attemptnumber > 0)){
            $attemptnumber = $submission->attemptnumber - 1;     
        }
    
        if ($submissionisgraded = $DB->get_records('assign_grades', array('assignment'=>$assignment->id, 'userid'=>$userid, 'attemptnumber' => $attemptnumber), 'attemptnumber DESC', '*', 0, 1)) {
            $submissionisgraded = reset($submissionisgraded);
            if ($submissionisgraded->grade > -1){
              if ($submission->timemodified > $submissionisgraded->timemodified) {
                    $graded = false;  
                }else{
                    $graded = true;  
                }
            }else{
                $graded = false;
            }                
        }else {
            $graded = false;
        } 
           

        if ($submission->status == 'draft') {
            if($graded){
                return 'submitted';
            }else{
                return 'saved';
            }            
        }
        if ($submission->status == 'reopened') {
            if($graded){
                return 'submitted';
            }else{
                return 'waitinggrade';
            }            
        } 
        if ($submission->status == 'submitted') {
            if($graded){
                return 'submitted';
            }else{ 
                return 'waitinggrade';
            }  
        }        
    } else {
        return false;
    }
}

?>
