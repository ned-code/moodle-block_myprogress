<?php //$Id: lib.php,v 1.6 2010/01/18 18:07:36 mchurch Exp $
//
//function assignments_count_ungraded($assignment, $graded, $studentid, $show='unmarked', $extra=false) {
//    global $DB;     
//
//    if (empty($studentid)) {
//        return 0;
//    }
//
//    if (!empty($extra) && ($extra['type'] == 'fnassignment')) {
//        $subtable = 'fnassignment_submissions';
//    } else {
//        $subtable = 'assignment_submissions';
//    }
//    
//    if (($show == 'unmarked') || ($show == 'all')) {
//        $select = '(assignment = '.$assignment.') AND (userid = '.$studentid.') AND '.
//                  '(timemarked < timemodified) AND (timemodified > 0)';    
//        
//        return $DB->count_records_select($subtable, $select,array(), 'COUNT(DISTINCT userid)');        
//      
//    } else if ($show == 'completed') {
//        $select = '(assignment = '.$assignment.') AND (userid = '. $studentid .') AND '.
//                  '(timemarked >= timemodified) AND (timemodified > 0)';
//        $marked = $DB->count_records_select($subtable, $select, array(), 'COUNT(DISTINCT userid)');
//        return $marked;
//        
//    } else if ($show == 'unsubmitted') {
//        $select = '(assignment = '.$assignment.') AND (userid = '.$studentid.') AND '.
//                  '(timemodified > 0)';
//        $subbed = $DB->count_records_select($subtable, $select,array(), 'COUNT(DISTINCT userid)');
//        $unsubbed = abs(count($students) - $subbed);
//        return ($unsubbed);
//    } 
//    else if ($show == 'saved') {
//        $select = '(assignment = '.$assignment.') AND (userid = '.$studentid.') AND '.
//                  '(timemodified > 0) AND data1 = "" AND data2 = "" AND grade="-1"';          
//        $savedass = $DB->count_records_select($subtable, $select,array('data1'=>'','data2'=>'','timemarked'=>'0','grade'=>'-1'), 'COUNT(DISTINCT userid)');
////        $unsubbed = abs(count($students) - $subbed);
//        return ($savedass);
//    }
//    else {
//        return 0;
//    }
//}
//
////function fnassignment_count_ungraded($assignment, $graded, $students, $show='unmarked', $extra=false) {
////    
////    global $DB;
////    $studentlist = implode(',', array_keys($students));
////
////    if (empty($studentlist)) {
////        return 0;
////    }
////
////    $subtable = 'fnassignment_submissions';
////
////    if (($show == 'unmarked') || ($show == 'all')) {
////        $select = '(assignment = '.$assignment.') AND (userid in ('.$studentlist.')) AND '.
////                  '(timemarked < timemodified) AND (timemodified > 0)';
////        return $DB->count_records_select($subtable, $select,array(), 'COUNT(DISTINCT userid)');
////    } else if ($show == 'marked') {
////        $select = '(assignment = '.$assignment.') AND (userid in ('.$studentlist.')) AND '.
////                  '(timemarked >= timemodified) AND (timemodified > 0)';
////        $marked = $DB->count_records_select($subtable, $select,array(), 'COUNT(DISTINCT userid)');
////        return $marked;
////    } else if ($show == 'unsubmitted') {
////        $select = '(assignment = '.$assignment.') AND (userid in ('.$studentlist.')) AND '.
////                  '(timemodified <= 0)';
////        $unsubbed = $DB->count_records_select($subtable, $select,array(), 'COUNT(DISTINCT userid)');
////        $subbed = abs(count($students) - $unsubbed);
////        return ($unsubbed);
////    } else {
////        return 0;
////    }
////}
//
////function assignment_oldest_ungraded($assignment) {
////    global $CFG,$DB;    
////
////      $sql = 'SELECT MIN(timemodified) FROM {assignment_submissions} '.
////           'WHERE (assignment = '.$assignment.') AND (timemarked < timemodified) AND (timemodified > 0)';
////    return $DB->get_field_sql($sql);
////}
//
////function exercise_count_ungraded($exerciseid, $graded, $students) {
////    global $CFG;
////    global $DB;
////    require_once ($CFG->dirroot.'/mod/exercise/locallib.php');
////    $exercise = $DB->get_record('exercise', array('id'=>$exerciseid));
////    return exercise_count_unassessed_student_submissions($exercise);
////}
//
////function forum_count_ungraded($forumid, $graded, $students, $show='unmarked') {
////    global $CFG;
////    global $DB;
////
////    //Get students from forum_posts 
////                               
////   $fusers = $DB->get_records_sql("SELECT DISTINCT u.*
////                               FROM {forum_discussions} d
////                               INNER JOIN {forum_posts} p ON p.discussion = d.id
////                               INNER JOIN user u ON u.id = p.userid
////                               WHERE d.forum = $forumid");
////
////    if (is_array($fusers)) {
////        foreach ($fusers as $key => $user) {
////            if (!array_key_exists($key, $students)) {
////                unset($fusers[$key]);
////            }
////        }
////    }
////
////    if (($show == 'unmarked') || ($show == 'all')) {
////        if (empty($graded) && !empty($fusers)) {
////            return count($fusers);
////        } else if (empty($fusers)) {
////            return 0;
////        } else {
////            return (count($fusers) - count($graded));
////        }
////    } else if ($show == 'marked') {
////        return count($graded);
////    } else if ($show == 'unsubmitted') {
////        $numuns = count($students) - count($fusers);
////        return max(0, $numuns);
////    }
////} 
//
////function journal_count_ungraded($journalid, $graded, $students, $show='unmarked') {
////    global $DB;
////    $studentlist = implode(',', $graded);
////
////    if (empty($studentlist)) {
////        return 0;
////    }
////
////    if (($show == 'unmarked') || ($show == 'all')) {
////        $select = 'journal = '.$journalid.' AND userid in ('.$studentlist.') AND '.
////                  'timemarked < modified AND modified > 0';
////        return $DB->count_records_select('journal_entries', $select, 'COUNT(DISTINCT userid)');
////    } else if ($show == 'marked') {
////        $select = 'journal = '.$journalid.' AND userid in ('.$studentlist.') AND '.
////                  'timemarked < modified AND modified > 0';
////        $unmarked = $DB->count_records_select('journal_entries', $select, 'COUNT(DISTINCT userid)');
////        $select = 'journal = '.$journalid.' AND userid in ('.$studentlist.') AND '.
////                  'modified = 0';
////        $unsubbed = $DB->count_records_select('journal_entries', $select, 'COUNT(DISTINCT userid)');
////        return count($graded) - ($unsubbed + $unmarked);
////    } else if ($show == 'unsubmitted') {
////        $select = 'journal = '.$journalid.' AND userid in ('.$studentlist.') AND '.
////                  'modified = 0';
////        return $DB->count_records_select('journal_entries', $select, 'COUNT(DISTINCT userid)');
////    } else {
////        return 0;
////    }
////}
//
//// function journal_oldest_ungraded($journalid) {
////    global $CFG;
////    global $DB;
////    
////    $sql = 'SELECT MIN(modified) FROM {journal_entries} '.
////           'WHERE (journal = '.$journalid.') AND (timemarked < modified) AND (modified > 0)';
////    return $DB->get_field_sql($sql);
////}
//
//
//function count_unmarked_activity(&$course, $info='unmarked') {
//
//    global $CFG;
//    global $mods, $modnames, $modnamesplural, $modnamesused, $sections;
//    global $DB;
//    $context = get_context_instance(CONTEXT_COURSE, $course->id); 
//    $isteacheredit = has_capability('moodle/course:update', $context);   
//    $marker = has_capability('moodle/grade:viewall', $context);  
//    $isadmin = is_primary_admin($course->id);   
//
//    $context = get_context_instance(CONTEXT_COURSE, $course->id);
//    $markallgroups = file_exists($CFG->dirroot.'/blocks/fn_site_groups/db/access.php') &&
//                     has_capability('block/fn_site_groups:markallgroups', $context);
//
//    $currentgroup = get_current_group($course->id);
//    
//  
//    if ($currentgroup && !$markallgroups) {
//        $students = get_group_students($currentgroup, "u.lastname ASC");
//    } else {        
//          
//        $context = get_context_instance(CONTEXT_COURSE, $course->id); 
//        $students = get_users_by_capability($context, 'mod/assignment:submit', 'u.*', 'u.lastname ASC');               
//    }
//
//    $totungraded = 0;
//
///// Array of functions to call for grading purposes for modules.
//    $mod_grades_array = array(
//        'assignment' => '/mod/assignment/submissions.g8.html',
//        'fnassignment' => '/mod/fnassignment/submissions.g8.html',
//        'exercise' =>   '/mod/exercise/submissions.g8.html',
//        'journal' =>    '/mod/journal/report.g8.html',
//        'forum' =>      '/mod/forum/submissions.g8.html',
////        'lesson' =>     '/mod/lesson/custom',
////        'quiz' =>       '/mod/quiz/report.php?',
////        'workshop' =>   '/mod/workshop/submissions.g8.html'
//        );
//
///// Collect modules data
//
///// Search through all the modules, pulling out grade data
//    get_all_mods($course->id, $mods, $modnames, $modnamesplural, $modnamesused);
//    $sections = get_all_sections($course->id); // Sort everything the same as the course
//
//    for ($i=0; $i<=$course->numsections; $i++) {
//        
//        if (isset($sections[$i])) {   // should always be true
//            $section = $sections[$i];          
//            if ($section->sequence) {
//                $sectionmods = explode(",", $section->sequence);         
//                foreach ($sectionmods as $sectionmod) {
//                     if (empty($mods[$sectionmod])) {
//                                continue;
//                            }
//                            $mod = $mods[$sectionmod];
//                                              
//                    /// Don't count it if you can't see it.
//                    $mcontext = get_context_instance(CONTEXT_MODULE, $mod->id);                   
//                    if (!$mod->visible && !has_capability('moodle/course:viewhiddenactivities', $mcontext)) {
//                        continue;
//                    }
//                    $instance = $DB->get_record("$mod->modname", array("id"=>$mod->instance));                
//                    $libfile = "$CFG->dirroot/mod/$mod->modname/lib.php";
//                    if (file_exists($libfile)) {
//                        require_once($libfile);
//                        $gradefunction = $mod->modname."_grades";
//                        $gradefunction = $mod->modname."_get_user_grades";
//                      
/////     Hide activities from the gradebook if the 'hideingradebook' field is set.
/////
//
//                        if (function_exists($gradefunction) &&  // Skip modules without grade function
////                            (($mod->modname != 'forum') || ($instance->assessed == 2)) && // Only include forums that are assessed only by teachers.
//                            isset($mod_grades_array[$mod->modname])) {
//
//                        /// Use the object function for fnassignments.
//                            if (isset($instance->assignmenttype) && ($instance->assignmenttype == 'fnassignment')) {
//                                /// Don't count read confirmation assignments
//                                if ($instance->var1 == 0) {
//                                    $modgrades = false;
//                                } else {
//                                    include_once($CFG->dirroot.'/mod/fnassignment/type/fnassignment/fnassignment.class.php');
//                                    $fnass = new fnassignment_fnassignment($mod->id, $instance, $mod, $course);
//                                    $modgrades = $fnass->grades();
//                                    
//                                }
//                            // Only include forums that are assessed only by teachers.
//                            } else if (($mod->modname == 'forum') &&
//                                       (($instance->assessed <= 0) || !has_capability('mod/forum:rate', $mcontext))) {
//                                $modgrades = false;
//                            } else {
//                                $modgrades = new Object();
//                                if (!($modgrades->grades = $gradefunction($instance))) {
//                                    $modgrades->grades = array();
//                                }
//                            }
//                            if ($modgrades) {
//                            /// Store the number of ungraded entries for this group.
//                                if (is_array($modgrades->grades) && is_array($students)) {
//                                    $gradedarray = array_intersect(array_keys($students),
//                                                                   array_keys($modgrades->grades));
//                                    $numgraded = count($gradedarray);
//                                    $numstudents = count($students);
//                                    $ungradedfunction = $mod->modname.'_count_ungraded';
//                                    if (function_exists($ungradedfunction)) {
//                                        if (($mod->modname == 'assignment') && ($instance->assignmenttype == 'fnassignment')) {
//                                            $extra = array('type' => 'fnassignment');
//                                        } else {
//                                            $extra = false;
//                                        }
//                                        $ung = $ungradedfunction($instance->id, $gradedarray, $students, $info, $extra);
//                                        
//                                    } else {
//                                        $ung = $numstudents - $numgraded;
//                                    }
//                                    if ($marker) {
//                                         $totungraded += $ung;
//                                    }
//                                }
//                            }
//                        }
//                    }
//                }
//            }
//        }
//    } // a new Moodle nesting record? ;-)
//   
//    return $totungraded;
//
//}
