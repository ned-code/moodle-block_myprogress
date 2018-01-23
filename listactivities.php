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

require_once('../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/blocks/fn_myprogress/lib.php');
require_once($CFG->libdir . '/completionlib.php');

$id = required_param('id', PARAM_INT);      // Course ID.
$show = optional_param('show', 'notloggedin', PARAM_ALPHA);

// Paging options.
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 2, PARAM_INT);
$PAGE->set_url('/blocks/fn_myprogress/listactivities.php', array('id' => $id, 'show' => $show, 'navlevel' => 'top'));

if (!$course = $DB->get_record("course", array("id" => $id))) {
    print_error("Course ID was incorrect");
}

require_login($course);

$context = context_course::instance($course->id);
$istudent = has_capability('mod/assignment:submit', $context);

// Only student should see this!
if (!$istudent) {
    print_error("Only students can use this page!");
}

require_login($course->id);  // Sets up $COURSE and therefore the theme.
$completedactivities = 0;
$incompletedactivities = 0;
$savedactivities = 0;
$notattemptedactivities = 0;
$waitingforgradeactivities = 0;
$savedactivities = 0;

if ($activities = block_fn_myprogress_get_gradable_activities($course->id, $USER->id)) {
    list($completedactivities,
        $incompletedactivities,
        $savedactivities,
        $notattemptedactivities,
        $waitingforgradeactivities,
        $status
    ) = block_fn_myprogress_get_activity_status_numbers($activities, $USER->id);
}

// Switch to show soecific assignment.
switch ($show) {

    case 'completed':

        $activitiesresults = $completedactivities;
        $name = get_string('breadcrumb:activitiescompleted', 'block_fn_myprogress');
        $title = get_string('title:completed', 'block_fn_myprogress') . " (Total:" . $completedactivities . " Activities)";
        break;

    case 'incompleted':

        $activitiesresults = $incompletedactivities;
        $name = get_string('breadcrumb:activitiesincompleted', 'block_fn_myprogress');
        $title = get_string('title:incompleted', 'block_fn_myprogress') . " (Total:" . $incompletedactivities . " Activities)";
        break;

    case 'draft':

        $activitiesresults = $savedactivities;
        $name = get_string('breadcrumb:draft', 'block_fn_myprogress');
        $title = get_string('title:saved', 'block_fn_myprogress') . " (Total:" . $savedactivities . " Activities)";
        break;

    case 'notattempted':
        $activitiesresults = $notattemptedactivities;
        $name = get_string('breadcrumb:notattempted', 'block_fn_myprogress');
        $title = get_string('title:notattempted', 'block_fn_myprogress') . " (Total:" . $notattemptedactivities . " Activities)";
        break;

    case 'waitingforgrade':
        $activitiesresults = $waitingforgradeactivities;
        $name = get_string('breadcrumb:waitingforgrade', 'block_fn_myprogress');
        $title = get_string('title:waitingforgrade', 'block_fn_myprogress') . " (Total:" .
            $waitingforgradeactivities . " Activities)";
        break;
    default:
        break;
}

// Print header.
$PAGE->navbar->add($name);
$heading = $course->fullname;
$PAGE->set_title($title);
$PAGE->set_heading($heading);
echo $OUTPUT->header();

echo "<div id='mark-interface'>";
echo "<h4 class='head-title' style='padding-bottom:12px;'>$title</h4>\n";

// Use paging.
$totalcount = $activitiesresults;

echo html_writer::start_tag('table',
    array(
        'width' => '96%',
        'class' => 'markingcontainerList',
        'border' => '0',
        'cellpadding' => '0',
        'cellspacing' => '0',
        'align' => 'center'
    )
);
echo html_writer::start_tag('tr');
echo html_writer::start_tag('td', array('class' => 'intd'));

echo html_writer::start_tag('table',
    array(
        'id' => 'datatable',
        'width' => '100%',
        'border' => '0',
        'cellpadding' => '0',
        'cellspacing' => '0'
    )
);
if (($show == 'all'
        || $show == 'completed'
        || $show == 'incompleted'
        || $show == 'draft'
        || $show == 'notattempted'
        || $show == 'waitingforgrade'
    ) && $totalcount > 0 && $activities > 0) {
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th',  get_string('activitytype', 'block_fn_myprogress'),
        array('align' => 'center', 'width' => '15%')
    );
    echo html_writer::tag('th',  get_string('activityorresourcename', 'block_fn_myprogress'),
        array('align' => 'left', 'width' => '27%', 'style' => 'text-align:left;')
    );
    echo html_writer::tag('th',  get_string('yourgrade', 'block_fn_myprogress'),
        array('align' => 'left', 'width' => '15%', 'style' => 'text-align:left;')
    );
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
} else {
    html_writer::div(
        get_string('noactivitywithstatus', 'block_fn_myprogress', get_string($show, 'block_fn_myprogress')),
        '', array('style' => 'text-align:center; padding:12px;')
    );
}

foreach ($activities as $activity) {
    $modulecontext = context_module::instance($activity->id);
    // Show saved status under completed.
    if (($show == 'completed') && ($status[$activity->id] == 'passed')) {
        $status[$activity->id] = 'completed';
    }
    if (($status[$activity->id] != $show) && ($show != 'all')) {
        continue;
    }
    echo block_fn_myprogress_render_activity_row($activity);
}

echo html_writer::end_tag('table');
echo html_writer::end_tag('td');
echo html_writer::end_tag('tr');
echo html_writer::end_tag('table');
echo html_writer::end_tag('div');
echo $OUTPUT->footer($course);
