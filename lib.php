<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    block_fn_myprogress
 * @copyright  Michael Gardener <mgardener@cissq.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/assignment/lib.php');

function block_fn_myprogress_assignment_status_old($mod, $userid) {
    global $CFG, $DB, $SESSION;

    if (isset($SESSION->completioncache)) {
        unset($SESSION->completioncache);
    }

    if ($mod->modname == 'assignment') {
        if (!($assignment = $DB->get_record('assignment', array('id' => $mod->instance)))) {
            return false;
        }
        require_once($CFG->dirroot.'/mod/assignment/type/'.$assignment->assignmenttype.'/assignment.class.php');
        $assignmentclass = "assignment_$assignment->assignmenttype";
        $assignmentinstance = new $assignmentclass($mod->id, $assignment, $mod);

        if (!($submission = $assignmentinstance->get_submission($userid)) || empty($submission->timemodified)) {
            return false;
        }

        switch ($assignment->assignmenttype) {
            case "upload":
                if ($assignment->var4) { // If var4 enable then assignment can be saved.
                    if (!empty($submission->timemodified)
                        && (empty($submission->data2))
                        && (empty($submission->timemarked))) {
                        return 'saved';

                    } else if (!empty($submission->timemodified)
                        && ($submission->data2 = 'submitted')
                        && empty($submission->timemarked)) {
                        return 'submitted';
                    } else if (!empty($submission->timemodified)
                        && ($submission->data2 = 'submitted')
                        && ($submission->grade == -1)) {
                        return 'submitted';
                    }
                } else if (empty($submission->timemarked)) {
                    return 'submitted';
                }
                break;
            case "uploadsingle":
                if (empty($submission->timemarked)) {
                    return 'submitted';
                }
                break;
            case "online":
                if (empty($submission->timemarked)) {
                    return 'submitted';
                }
                break;
            case "offline":
                if (empty($submission->timemarked)) {
                    return 'submitted';
                }
                break;
        }
    } else if ($mod->modname == 'assign') {
        if (!($assignment = $DB->get_record('assign', array('id' => $mod->instance)))) {
            return false; // Doesn't exist.
        }

        if (!$submission = $DB->get_records('assign_submission',
            array('assignment' => $assignment->id, 'userid' => $userid), 'attemptnumber DESC', '*', 0, 1)
        ) {
            return false;
        } else {
            $submission = reset($submission);
        }

        $attemptnumber = $submission->attemptnumber;

        if (($submission->status == 'reopened') && ($submission->attemptnumber > 0)) {
            $attemptnumber = $submission->attemptnumber - 1;
        }

        if ($submissionisgraded = $DB->get_records('assign_grades',
            array('assignment' => $assignment->id, 'userid' => $userid,
                'attemptnumber' => $attemptnumber), 'attemptnumber DESC', '*', 0, 1
        )) {
            $submissionisgraded = reset($submissionisgraded);
            if ($submissionisgraded->grade > -1) {
                if (($submission->timemodified > $submissionisgraded->timemodified)
                    || ($submission->attemptnumber > $submissionisgraded->attemptnumber)) {
                    $graded = false;
                } else {
                    $graded = true;
                }
            } else {
                $graded = false;
            }
        } else {
            $graded = false;
        }

        if ($submission->status == 'draft') {
            if ($graded) {
                return 'submitted';
            } else {
                return 'saved';
            }
        }
        if ($submission->status == 'reopened') {
            return 'submitted';
        }
        if ($submission->status == 'submitted') {
            if ($graded) {
                return 'submitted';
            } else {
                return 'waitinggrade';
            }
        }
    } else {
        return false;
    }
}

function block_fn_myprogress_get_gradable_activities($courseid, $userid) {
    global $DB;

    $modinfo = get_fast_modinfo($courseid, $userid);
    $activities = $modinfo->get_cms();

    foreach ($activities as $key => $mod) {
        $modulecontext = context_module::instance($mod->id);
        if (!$mod->visible && !has_capability('moodle/course:viewhiddenactivities', $modulecontext, $userid)) {
            unset($activities[$key]);;
        }
        $instance = $DB->get_record($mod->modname, array('id' => $mod->instance));
        if (($mod->modname == 'forum') && !($instance->assessed > 0)) {
            unset($activities[$key]);
        }

        if (!$item = $DB->get_record('grade_items',
            array(
                "itemtype" => 'mod',
                "itemmodule" => $mod->modname,
                "iteminstance" => $mod->instance
            )
        )) {
            unset($activities[$key]);
        }
    }
    return $activities;
}

function block_fn_myprogress_get_activity_status_numbers($activities, $userid=0) {
    global $CFG, $DB, $USER;

    if (!$userid) {
        $userid = $USER->id;
    }

    $numberofitem = 0;
    $completedactivities = 0;
    $incompletedactivities = 0;
    $savedactivities = 0;
    $notattemptedactivities = 0;
    $waitingforgradeactivities = 0;
    $status = array();

    foreach ($activities as $key => $mod) {
        $instance = $DB->get_record($mod->modname, array('id' => $mod->instance));
        if (!$item = $DB->get_record('grade_items',
            array(
                "itemtype" => 'mod',
                "itemmodule" => $mod->modname,
                "iteminstance" => $mod->instance
            )
        )) {
            $item = new stdClass();
            $item->gradepass = 0;
            $item->grademax = 0;
        }

        $libfile = $CFG->dirroot . '/mod/' . $mod->modname . '/lib.php';
        if (file_exists($libfile)) {
            require_once($libfile);
            $gradefunction = $mod->modname . "_get_user_grades";

            if (($mod->modname != 'forum') || ($instance->assessed > 0)) {
                if (function_exists($gradefunction)) {
                    ++$numberofitem;
                    if (($mod->modname == 'quiz') || ($mod->modname == 'forum')) {
                        if ($grade = $gradefunction($instance, $userid)) {
                            if ($item->gradepass > 0) {
                                if ($grade[$userid]->rawgrade >= $item->gradepass) {
                                    // Passed.
                                    $status[$key] = 'completed';
                                    $completedactivities++;
                                } else {
                                    // Fail.
                                    $status[$key] = 'incompleted';
                                    $incompletedactivities++;
                                }
                            } else {
                                // Graded (grade-to-pass is not set).
                                $status[$key] = 'completed';
                                $completedactivities++;
                            }
                        } else {
                            // Ungraded.
                            $status[$key] = 'notattempted';
                            $notattemptedactivities++;
                        }
                    } else if ($mod->modname == 'journal') {
                        if ($grade = $gradefunction($instance, $userid)) {
                            if (is_numeric($grade[$userid]->rawgrade)) {
                                if ($item->gradepass > 0) {
                                    if ($grade[$userid]->rawgrade >= $item->gradepass) {
                                        // Passed.
                                        $status[$key] = 'completed';
                                        $completedactivities++;
                                    } else {
                                        // Fail.
                                        $status[$key] = 'incompleted';
                                        $incompletedactivities++;
                                    }
                                } else {
                                    // Graded (grade-to-pass is not set).
                                    $status[$key] = 'completed';
                                    $completedactivities++;
                                }
                            } else {
                                // Unmarked.
                                $status[$key] = 'notattempted';
                                $notattemptedactivities++;
                            }
                        } else {
                            // Ungraded.
                            $status[$key] = 'notattempted';
                            $notattemptedactivities++;
                        }
                    } else if ($modstatus = block_fn_myprogress_assignment_status($mod, $userid, true)) {
                        switch ($modstatus) {
                            case 'submitted':
                                if ($grade = block_fn_myprogress_gradebook_grade($item->id, $userid)) {
                                    if ($item->gradepass > 0) {
                                        if ($grade >= $item->gradepass) {
                                            // Passed.
                                            $status[$key] = 'completed';
                                            $completedactivities++;
                                        } else {
                                            // Fail.
                                            $status[$key] = 'incompleted';
                                            $incompletedactivities++;
                                        }
                                    } else {
                                        // Graded (grade-to-pass is not set).
                                        $status[$key] = 'passed';
                                        $completedactivities++;
                                    }
                                }
                                break;
                            case 'saved':
                                // Saved.
                                $status[$key] = 'draft';
                                $savedactivities++;
                                break;
                            case 'waitinggrade':
                                // Waitinggrade.
                                $status[$key] = 'waitingforgrade';
                                $waitingforgradeactivities++;
                                break;
                        }
                    } else {
                        // Ungraded.
                        $status[$key] = 'notattempted';
                        $notattemptedactivities++;
                    }
                }
            }
        }
    }
    return array(
        $completedactivities,
        $incompletedactivities,
        $savedactivities,
        $notattemptedactivities,
        $waitingforgradeactivities,
        $status
    );
}

function block_fn_myprogress_assignment_status($mod, $userid) {
    global $CFG, $DB, $SESSION;

    if (isset($SESSION->completioncache)) {
        unset($SESSION->completioncache);
    }

    if ($mod->modname == 'assignment') {
        if (!($assignment = $DB->get_record('assignment', array('id' => $mod->instance)))) {
            return false;
        }
        require_once($CFG->dirroot.'/mod/assignment/type/'.$assignment->assignmenttype.'/assignment.class.php');
        $assignmentclass = "assignment_$assignment->assignmenttype";
        $assignmentinstance = new $assignmentclass($mod->id, $assignment, $mod);

        if (!($submission = $assignmentinstance->get_submission($userid)) || empty($submission->timemodified)) {
            return false;
        }

        switch ($assignment->assignmenttype) {
            case "upload":
                if ($assignment->var4) {
                    if (!empty($submission->timemodified)
                        && (empty($submission->data2))
                        && (empty($submission->timemarked))) {
                        return 'saved';

                    } else if (!empty($submission->timemodified)
                        && ($submission->data2 = 'submitted')
                        && empty($submission->timemarked)) {
                        return 'submitted';
                    } else if (!empty($submission->timemodified)
                        && ($submission->data2 = 'submitted')
                        && ($submission->grade == -1)) {
                        return 'submitted';
                    }
                } else if (empty($submission->timemarked)) {
                    return 'submitted';
                }
                break;
            case "uploadsingle":
                if (empty($submission->timemarked)) {
                    return 'submitted';
                }
                break;
            case "online":
                if (empty($submission->timemarked)) {
                    return 'submitted';
                }
                break;
            case "offline":
                if (empty($submission->timemarked)) {
                    return 'submitted';
                }
                break;
        }
    } else if ($mod->modname == 'assign') {
        if (!($assignment = $DB->get_record('assign', array('id' => $mod->instance)))) {
            return false;
        }

        if (!$submission = $DB->get_records('assign_submission', array('assignment' => $assignment->id,
            'userid' => $userid), 'attemptnumber DESC', '*', 0, 1)) {
            return false;
        } else {
            $submission = reset($submission);
        }

        $attemptnumber = $submission->attemptnumber;

        if (($submission->status == 'reopened') && ($submission->attemptnumber > 0)) {
            $attemptnumber = $submission->attemptnumber - 1;
        }

        if ($submissionisgraded = $DB->get_records('assign_grades', array('assignment' => $assignment->id, 'userid' => $userid,
            'attemptnumber' => $attemptnumber), 'attemptnumber DESC', '*', 0, 1)) {
            $submissionisgraded = reset($submissionisgraded);
            if ($submissionisgraded->grade > -1) {
                if (($submission->timemodified > $submissionisgraded->timemodified)
                    || ($submission->attemptnumber > $submissionisgraded->attemptnumber)) {
                    $graded = false;
                } else {
                    $graded = true;
                }
            } else {
                $graded = false;
            }
        } else {
            $graded = false;
        }

        if ($submission->status == 'draft') {
            if ($graded) {
                return 'submitted';
            } else {
                return 'saved';
            }
        }
        if ($submission->status == 'reopened') {
            return 'submitted';
        }
        if ($submission->status == 'submitted') {
            if ($graded) {
                return 'submitted';
            } else {
                return 'waitinggrade';
            }
        }
    } else {
        return false;
    }
}

function block_fn_myprogress_gradebook_grade ($itemid, $userid) {
    global $DB;

    if ($grade = $DB->get_record('grade_grades', array('itemid' => $itemid, 'userid' => $userid))) {
        return $grade->finalgrade;
    } else {
        return false;
    }
}

function block_fn_myprogress_render_activity_row($activity, $reporttable=null) {
    global $OUTPUT;

    $output = '';

    list($gradepercentage, $gradeformatted) = block_fn_myprogress_get_formatted_grade($activity, 0, $reporttable);
    $modicon = html_writer::img($OUTPUT->pix_url('icon', $activity->modname), '',
        array('height' => '20', 'width' => '20')
    );
    $pluginfullname = get_string('pluginname', $activity->modname);
    $modurl = new moodle_url('/mod/'.$activity->modname.'/view.php', array('id' => $activity->id));
    $output = html_writer::tag('tr',
        html_writer::tag('td', $pluginfullname, array('align' => 'center')).
        html_writer::tag('td',
            html_writer::link($modurl, $modicon).' '.
            html_writer::link($modurl, $activity->name),
            array('align' => 'left')
        ).
        html_writer::tag('td', $gradeformatted, array('data-sort-value' => $gradepercentage, 'align' => 'right'))
    );
    return $output;
}

function block_fn_myprogress_get_formatted_grade($mod, $userid=0) {
    global $DB, $USER;

    if (!$userid) {
        $userid = $USER->id;
    }
    $gradepercentage = 0;
    $gradeformatted = '-';

    if ($gradeitem = $DB->get_record('grade_items',
        array('itemtype' => 'mod', 'itemmodule' => $mod->modname, 'iteminstance' => $mod->instance))
    ) {
        $gradeformatted = '/' . round($gradeitem->grademax);
        if ($grade = $DB->get_record('grade_grades', array('itemid' => $gradeitem->id, 'userid' => $userid))) {
            if (!is_null($grade->finalgrade)) {
                $gradepercentage = round(($grade->finalgrade / $grade->rawgrademax) * 100);
                $gradeformatted = round($grade->finalgrade) . '/' . round($grade->rawgrademax) . ' (' . $gradepercentage . '%)';
            }
        }
    }
    return array($gradepercentage, $gradeformatted);
}