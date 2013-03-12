<?php

require_once('../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/blocks/fn_myprogress/lib.php');
require_once($CFG->libdir . '/completionlib.php');

global $CFG, $DB, $OUTPUT, $PAGE, $COURSE;
//Check sesubmission plugin
if ($assignCheck = $DB->get_record_sql("SELECT * FROM {$CFG->prefix}assign LIMIT 0, 1")){
    if(isset($assignCheck->resubmission)){
        $resubmission = true;
    }else{
        $resubmission = false;
    }
}else{
    $resubmission = false;
}

$USINGHTMLEDITOR = false;

$id = required_param('id', PARAM_INT);      // course id
$show = optional_param('show', 'notloggedin', PARAM_ALPHA);

//  Paging options:
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 2, PARAM_INT);
$PAGE->set_url('/blocks/fn_myprogress/listactivities.php', array('id' => $id, 'show' => $show, 'navlevel' => 'top'));

if (!$course = $DB->get_record("course", array("id" => $id))) {
    print_error("Course ID was incorrect");
}

require_login($course);
$context = get_context_instance(CONTEXT_COURSE, $course->id);
$istudent = has_capability('mod/assignment:submit', $context);

// only student should see this!
if (!$istudent) {
    print_error("Only students can use this page!");
}

require_login($course->id);  /// Sets up $COURSE and therefore the theme.
$completedactivities = 0;
$incompletedactivities = 0;
$savedactivities = 0;
$notattemptedactivities = 0;
$waitingforgradeactivities = 0;
$savedactivities = 0;

$completion = new completion_info($course);
$activities = $completion->get_activities();

if ($completion->is_enabled()) {
    foreach ($activities as $activity) {
        if (!$activity->visible) {
            continue;
        }
        $data = $completion->get_data($activity, true, $USER->id, null);
        $completionstate = $data->completionstate;
        $assignment_status = assignment_status($activity, $USER->id, $resubmission);
        if ($completionstate == 0) {
            if (($activity->module == 1)
                    && ($activity->modname == 'assignment' || $activity->modname == 'assign')
                    && ($activity->completion == 2)
                    && $assignment_status) {
                //grab assignment status
                
                if (isset($assignment_status)) {
                    if ($assignment_status == 'saved') {
                        $savedactivities++;
                    } else if ($assignment_status == 'submitted') {
                        $incompletedactivities++;
                    } else if ($assignment_status == 'waitinggrade') {
                        $waitingforgradeactivities++;
                    }
                }
            } else {
                $notattemptedactivities++;
            }
        } elseif ($completionstate == 1 || $completionstate == 2) {
            if (isset($assignment_status)) {
                if ($assignment_status == 'saved') {
                    $savedactivities++;
                } else if ($assignment_status == 'submitted') {
                    $completedactivities++;
                } else if ($assignment_status == 'waitinggrade') {
                    $waitingforgradeactivities++;
                }
            }  
        } elseif ($completionstate == 3) {
            if (($activity->module == 1)
                    && ($activity->modname == 'assignment' || $activity->modname == 'assign')
                    && ($activity->completion == 2)
                    && $assignment_status) {
                        
                if (isset($assignment_status)) {
                    if ($assignment_status == 'saved') {
                        $savedactivities++;
                    } else if ($assignment_status == 'submitted') {
                        $incompletedactivities++;
                    } else if ($assignment_status == 'waitinggrade') {
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
}


// switch to show soecific assignment
switch ($show) {

    case 'completed':

        $activities_results = $completedactivities;
        $name = get_string('breadcrumb:activitiescompleted', 'block_fn_myprogress');
        $title = get_string('title:completed', 'block_fn_myprogress') . " (Total:" . $completedactivities . " Activities)";
        break;

    case 'incompleted':

        $activities_results = $incompletedactivities;
        $name = get_string('breadcrumb:activitiesincompleted', 'block_fn_myprogress');
        $title = get_string('title:incompleted', 'block_fn_myprogress') . " (Total:" . $incompletedactivities . " Activities)";
        break;

    case 'draft':

        $activities_results = $savedactivities;
        $name = get_string('breadcrumb:draft', 'block_fn_myprogress');
        $title = get_string('title:saved', 'block_fn_myprogress') . " (Total:" . $savedactivities . " Activities)";
        break;

    case 'notattempted':
        $activities_results = $notattemptedactivities;
        $name = get_string('breadcrumb:notattempted', 'block_fn_myprogress');
        $title = get_string('title:notattempted', 'block_fn_myprogress') . " (Total:" . $notattemptedactivities . " Activities)";
        break;

    case 'waitingforgrade':
        $activities_results = $waitingforgradeactivities;
        $name = get_string('breadcrumb:waitingforgrade', 'block_fn_myprogress');
        $title = get_string('title:waitingforgrade', 'block_fn_myprogress') . " (Total:" . $waitingforgradeactivities . " Activities)";
        break;
    default:
        break;
}

/// Print header
$navlinks = array(array('name' => $name, 'link' => '', 'type' => 'misc'));
$navigation = build_navigation($navlinks);
$heading = $course->fullname;
print_header_simple($title, $heading, $navigation, '', '', true, '', '');

echo "<div id='mark-interface'>";
echo "<h4 class='head-title' style='padding-bottom:12px;'>$title</h4>\n";

// use paging
$totalcount = $activities_results;
//$baseurl = 'listactivities.php?id=' . $id . '&show=' . $show . '&navlevel=top';
//$pagingbar = new paging_bar($totalcount, $page, $perpage, $baseurl, 'page');
//echo $OUTPUT->render($pagingbar);

echo '<table width="96%" class="markingcontainerList" border="0" cellpadding="0" cellspacing="0" align="center">' . '<tr><td class="intd">';

echo '<table  width="100%" border="0" cellpadding="0" cellspacing="0">';
if (($show == 'completed' || $show == 'incompleted' || $show == 'draft' || $show == 'notattempted' || $show == 'waitingforgrade') && $totalcount > 0 && $activities > 0) {
    echo "<tr>";
    echo "<th align='center' width='15%'><strong>Activity type </strong></th>";
    echo "<th align='left' width='67%' style='text-align:left;'><strong>Activity or Resource Name </strong></th>";
    //echo "<th align='center' width='18%'><strong>Section <strong></th>";
    echo "</tr>";
} else {
    echo '<div style="text-align:center; padding:12px;">';
    echo "No activity with status " . get_string($show, 'block_fn_myprogress') . "";
    echo "</div>";
}
// iterate
//for ($i = ($page * $perpage); ($i < ($page * $perpage) + $perpage) && ($i < $totalcount); $i++) {

if ($show == 'completed') {
    if ($activities) {
        foreach ($activities as $activity) {
            if (!$activity->visible) {
                continue;
            }

            $data = $completion->get_data($activity, false, $userid = 0, null);
            $activitystate = $data->completionstate;
            if ($activitystate == 1 || $activitystate == 2) {
                echo "<tr><td align='center'>\n";
                $modtype = $DB->get_field('modules', 'name', array('id' => $activity->module));
                $modicon = "<IMG BORDER=0 VALIGN=absmiddle SRC=\"$CFG->wwwroot/mod/$modtype/pix/icon.gif\" HEIGHT=\"16\" WIDTH=\"16\" >";
                echo ($modtype == 'assign') ? 'assignment' : $modtype;
                echo "</td>\n";
                echo "<td align='left'><a href='" . $CFG->wwwroot . "/mod/$modtype/view.php?id=$data->coursemoduleid'>$modicon</a><a href='" . $CFG->wwwroot . "/mod/$modtype/view.php?id=$data->coursemoduleid' style=\"padding-left:4px\">" . $activity->name . "</a></td>\n";
                // echo "<td align='center'>Section-$activity->section</td></tr>\n";
            }
        }
    }
}
// if show is incompleted
else if ($show == 'incompleted') {
    if ($activities) {
        foreach ($activities as $activity) {
            if (!$activity->visible) {
                continue;
            }
            $data = $completion->get_data($activity, true, $userid = 0, null);
            $activitystate = $data->completionstate;
            $assignment_status = assignment_status($activity, $USER->id, $resubmission);
            if ($activitystate == 3) {
                if (($activity->module == 1)
                        && ($activity->modname == 'assignment' || $activity->modname == 'assign')
                        && ($activity->completion == 2)
                        && $assignment_status) {
                    
                    if ($assignment_status == 'submitted'){
                        echo "<tr><td align='center'>\n";
                        $modtype = $DB->get_field('modules', 'name', array('id' => $activity->module));
                        $modicon = "<IMG BORDER=0 VALIGN=absmiddle SRC=\"$CFG->wwwroot/mod/$modtype/pix/icon.gif\" HEIGHT=\"16\" WIDTH=\"16\" >";
                        echo ($modtype == 'assign') ? 'assignment' : $modtype;
                        echo "</td>\n";
                        echo "<td align='left'><a href='" . $CFG->wwwroot . "/mod/$modtype/view.php?id=$data->coursemoduleid'>$modicon</a><a href='" . $CFG->wwwroot . "/mod/$modtype/view.php?id=$data->coursemoduleid' style=\"padding-left:4px\">" . $activity->name . "</a></td>\n";
                        // echo "<td align='center'>Section-$activity->section</td></tr>\n";
                    }else{
                        continue;
                    }
                }else{
                    echo "<tr><td align='center'>\n";
                    $modtype = $DB->get_field('modules', 'name', array('id' => $activity->module));
                    $modicon = "<IMG BORDER=0 VALIGN=absmiddle SRC=\"$CFG->wwwroot/mod/$modtype/pix/icon.gif\" HEIGHT=\"16\" WIDTH=\"16\" >";
                    echo ($modtype == 'assign') ? 'assignment' : $modtype;
                    echo "</td>\n";
                    echo "<td align='left'><a href='" . $CFG->wwwroot . "/mod/$modtype/view.php?id=$data->coursemoduleid'>$modicon</a><a href='" . $CFG->wwwroot . "/mod/$modtype/view.php?id=$data->coursemoduleid' style=\"padding-left:4px\">" . $activity->name . "</a></td>\n";
                    // echo "<td align='center'>Section-$activity->section</td></tr>\n";                    
                }
            }
        }
    }
}
// if show is nonattempted
else if ($show == 'notattempted') {
    if ($activities) {
        foreach ($activities as $activity) {
            if (!$activity->visible) {
                continue;
            }
            $data = $completion->get_data($activity, true, $USER->id, null);
            $activitystate = $data->completionstate;
            $assignment_status = assignment_status($activity, $USER->id, $resubmission);
            if ($activitystate == 0) {
                if (($activity->module == 1)
                        && ($activity->modname == 'assignment' || $activity->modname == 'assign')
                        && ($activity->completion == 2)
                        && $assignment_status) {
                    continue;
                }
                echo "<tr><td align='center'>\n";
                $modtype = $DB->get_field('modules', 'name', array('id' => $activity->module));
                $modicon = "<IMG BORDER=0 VALIGN=absmiddle SRC=\"$CFG->wwwroot/mod/$modtype/pix/icon.gif\" HEIGHT=\"16\" WIDTH=\"16\" >";
                echo ($modtype == 'assign') ? 'assignment' : $modtype;
                echo "</td>\n";
                echo "<td align='left'><a href='" . $CFG->wwwroot . "/mod/$modtype/view.php?id=$data->coursemoduleid'>$modicon</a><a href='" . $CFG->wwwroot . "/mod/$modtype/view.php?id=$data->coursemoduleid' style=\"padding-left:4px\">" . $activity->name . "</a></td>\n";
                // echo "<td align='center'>Section-$activity->section</td></tr>\n";
            }
        }
    }
}
// if show is waitinh for grade
else if ($show == 'waitingforgrade') {
    if ($activities) {
        foreach ($activities as $activity) {
            if (!$activity->visible) {
                continue;
            }
            $data = $completion->get_data($activity, true, $USER->id, null);
            $activitystate = $data->completionstate;
            $assignment_status = assignment_status($activity, $USER->id, $resubmission);
            if (($activitystate == 0)||($activitystate == 1)||($activitystate == 2)||($activitystate == 3)) {
                if (($activity->module == 1)
                        && ($activity->modname == 'assignment' || $activity->modname == 'assign')
                        && ($activity->completion == 2)
                        && $assignment_status) {
                    if (isset($assignment_status)) {
                        if ($assignment_status == 'waitinggrade') {
                            echo "<tr><td align='center'>\n";
                            $modtype = $DB->get_field('modules', 'name', array('id' => $activity->module));
                            $modicon = "<IMG BORDER=0 VALIGN=absmiddle SRC=\"$CFG->wwwroot/mod/$modtype/pix/icon.gif\" HEIGHT=\"16\" WIDTH=\"16\" >";
                            echo ($modtype == 'assign') ? 'assignment' : $modtype;
                            echo "</td>\n";
                            echo "<td align='left'><a href='" . $CFG->wwwroot . "/mod/$modtype/view.php?id=$data->coursemoduleid'>$modicon</a><a href='" . $CFG->wwwroot . "/mod/$modtype/view.php?id=$data->coursemoduleid' style=\"padding-left:4px\">" . $activity->name . "</a></td>\n";
                            // echo "<td align='center'>Section-$activity->section</td></tr>\n";                            
                        }
                    }
                }
            }
        }
    }
} else if ($show == 'draft') {
    if ($activities) {
        foreach ($activities as $activity) {
            if (!$activity->visible) {
                continue;
            }
            $data = $completion->get_data($activity, true, $userid = 0, null);
            $activitystate = $data->completionstate;
            $assignment_status = assignment_status($activity, $USER->id, $resubmission);
            if (($activitystate == 0) || ($activitystate == 1) || ($activitystate == 2) || ($activitystate == 3)) {
                if (($activity->module == 1)
                        && ($activity->modname == 'assignment' || $activity->modname == 'assign')
                        && ($activity->completion == 2)
                        && $assignment_status) {
                    if (isset($assignment_status)) {
                        if ($assignment_status == 'saved') {
                            echo "<tr><td align='center'>\n";
                            $modtype = $DB->get_field('modules', 'name', array('id' => $activity->module));
                            //echo ($modtype == 'assign') ? 'assignment' : $modtype;
                            $modicon = "<IMG BORDER=0 VALIGN=absmiddle SRC=\"$CFG->wwwroot/mod/$modtype/pix/icon.gif\" HEIGHT=\"16\" WIDTH=\"16\" >";
                            echo ($modtype == 'assign') ? 'assignment' : $modtype;
                            echo "</td>\n";
                            echo "<td align='left'><a href='" . $CFG->wwwroot . "/mod/$modtype/view.php?id=$data->coursemoduleid'>$modicon</a><a href='" . $CFG->wwwroot . "/mod/$modtype/view.php?id=$data->coursemoduleid' style=\"padding-left:4px\">" . $activity->name . "</a></td>\n";
                            //echo "<td align='center'>Section-$activity->section</td></tr>\n";
                        }
                    }
                }
            }
        }
    }
} else {
    
}
//}
echo"</table>\n";

echo '</td></tr></table>';

echo "</div>";
echo $OUTPUT->footer($course);
