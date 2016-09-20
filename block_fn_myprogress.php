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

require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/blocks/fn_myprogress/lib.php');

/**
 *
 * base class for block
 * @author     MoodleFn
 */
class block_fn_myprogress extends block_list {

    public function init() {
        $this->title = get_string('blocktitle', 'block_fn_myprogress');
    }

    /**
     * Constrols the block title based on instance configuration
     *
     * @return bool
     */
    public function specialization() {
        global $course;

        // Need the bigger course object.
        $this->course = $course;

        if (empty($this->config->title)) {
            $this->title = get_string('blocktitle', 'block_fn_myprogress');
        } else {
            $this->title = $this->config->title;
        }
    }

    /**
     * Return the block content *
     * @return block content
     * @todo Finish documenting this function
     */
    public function get_content() {
        global $CFG, $SESSION, $COURSE, $USER, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }
        $context = context_course::instance($COURSE->id);

        if (isset($SESSION->completioncache)) {
            unset($SESSION->completioncache);
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        if (!has_capability('block/fn_myprogress:viewblock', $context) || is_siteadmin($USER)) {
            return $this->content;
        }

        $completedactivities = 0;
        $incompletedactivities = 0;
        $savedactivities = 0;
        $notattemptedactivities = 0;
        $waitingforgradeactivities = 0;

        $course = $this->page->course;

        $completion = new completion_info($course);
        $activities = $completion->get_activities();

        // Draft config.
        if (isset($this->config->showdraft)) {
            $showdraft = $this->config->showdraft;
        } else {
            $showdraft = get_config('block_fn_myprogress', 'showdraft');
        }

        if ($completion->is_enabled() && !empty($completion)) {

            foreach ($activities as $activity) {
                if (!$activity->visible) {
                    continue;
                }

                $data = $completion->get_data($activity, true, $userid = 0, null);

                $completionstate = $data->completionstate;
                $assignmentstatus = block_fn_myprogress_assignment_status($activity, $USER->id);

                // COMPLETION_INCOMPLETE.
                if ($completionstate == 0) {
                    // Show activity as complete when conditions are met.
                    if (($activity->module == 1)
                        && ($activity->modname == 'assignment' || $activity->modname == 'assign')
                        && ($activity->completion == 2)
                        && $assignmentstatus) {

                        if (isset($assignmentstatus)) {
                            if ($assignmentstatus == 'saved') {
                                $savedactivities++;
                            } else if ($assignmentstatus == 'submitted') {
                                $notattemptedactivities++;
                            } else if ($assignmentstatus == 'waitinggrade') {
                                $waitingforgradeactivities++;
                            }
                        } else {
                            $notattemptedactivities++;
                        }
                    } else {
                        $notattemptedactivities++;
                    }
                    // COMPLETION_COMPLETE - COMPLETION_COMPLETE_PASS.
                } else if ($completionstate == 1 || $completionstate == 2) {
                    if (($activity->module == 1)
                        && ($activity->modname == 'assignment' || $activity->modname == 'assign')
                        && ($activity->completion == 2)
                        && $assignmentstatus) {

                        if (isset($assignmentstatus)) {
                            if ($assignmentstatus == 'saved') {
                                $savedactivities++;
                            } else if ($assignmentstatus == 'submitted') {
                                $completedactivities++;
                            } else if ($assignmentstatus == 'waitinggrade') {
                                $waitingforgradeactivities++;
                            }
                        } else {
                            $completedactivities++;
                        }
                    } else {
                        $completedactivities++;
                    }

                    // COMPLETION_COMPLETE_FAIL.
                } else if ($completionstate == 3) {
                    // Show activity as complete when conditions are met.
                    if (($activity->module == 1)
                        && ($activity->modname == 'assignment' || $activity->modname == 'assign')
                        && ($activity->completion == 2)
                        && $assignmentstatus) {

                        if (isset($assignmentstatus)) {
                            if ($assignmentstatus == 'saved') {
                                $savedactivities++;
                            } else if ($assignmentstatus == 'submitted') {
                                $incompletedactivities++;
                            } else if ($assignmentstatus == 'waitinggrade') {
                                $waitingforgradeactivities++;
                            }
                        } else {
                            $incompletedactivities++;
                        }
                    } else {
                        $incompletedactivities++;
                    }
                }
            }

            if (has_capability('block/fn_myprogress:viewblock', $context) && !is_siteadmin($USER)) {
                // Completed.
                if ($incompletedactivities) {
                    $this->content->items[] = '<a href="' . $CFG->wwwroot . '/blocks/fn_myprogress/listactivities.php?id=' .
                        $course->id . '&show=completed' .
                        '&navlevel=top">' . $completedactivities . ' '.
                        get_string('completedsuccessful', 'block_fn_myprogress').'</a>';
                } else {
                    $this->content->items[] = '<a href="' . $CFG->wwwroot . '/blocks/fn_myprogress/listactivities.php?id=' .
                        $course->id . '&show=completed' .
                        '&navlevel=top">' . $completedactivities . ' '.
                        get_string('completed', 'block_fn_myprogress').'</a>';
                }
                $this->content->icons[] = '<img src="'.
                    $OUTPUT->pix_url('completed', 'block_fn_myprogress').'" class="icon" alt="">';

                // Incomplete.
                if ($incompletedactivities) {
                    $this->content->items[] = '<a href="' . $CFG->wwwroot . '/blocks/fn_myprogress/listactivities.php?id=' .
                        $course->id . '&show=incompleted' .
                        '&navlevel=top">' . $incompletedactivities . ' ' .
                        get_string('completedunsuccessful', 'block_fn_myprogress') . '</a>';

                    $this->content->icons[] = '<img src="' .
                        $OUTPUT->pix_url('incomplete', 'block_fn_myprogress') . '" class="icon" alt="">';
                }

                // Draft.
                if ($showdraft && $savedactivities) {
                    $this->content->items[] = '<a href="' . $CFG->wwwroot . '/blocks/fn_myprogress/listactivities.php?id=' .
                        $course->id . '&show=draft' .
                        '&navlevel=top">' . $savedactivities . ' '.
                        get_string('draft', 'block_fn_myprogress').'</a>';

                    $this->content->icons[] = '<img src="' .
                        $OUTPUT->pix_url('saved', 'block_fn_myprogress') . '" class="icon" alt="">';
                }

                // Not Attempted.
                $this->content->items[] = '<a href="' . $CFG->wwwroot . '/blocks/fn_myprogress/listactivities.php?id=' .
                    $course->id . '&show=notattempted' .
                    '&navlevel=top">' . $notattemptedactivities . ' '.
                    get_string('notattempted', 'block_fn_myprogress').'</a>';

                $this->content->icons[] = '<img src="'.
                    $OUTPUT->pix_url('notattempted', 'block_fn_myprogress').'" class="icon" alt="">';

                // Waiting for grade.
                $this->content->items[] = '<a href="' . $CFG->wwwroot . '/blocks/fn_myprogress/listactivities.php?id=' .
                    $course->id . '&show=waitingforgrade' .
                    '&navlevel=top">' . $waitingforgradeactivities . ' '.
                    get_string('waitingforgrade', 'block_fn_myprogress').'</a>';

                $this->content->icons[] = '<img src="'.
                    $OUTPUT->pix_url('unmarked', 'block_fn_myprogress').'" class="icon" alt="">';
            }
        } else {
            $this->content->items[] = get_string('disabledviewmessage', 'block_fn_myprogress');
            $this->content->icons[] = '<img src="'.
                $OUTPUT->pix_url('warning', 'block_fn_myprogress').'" class="icon" alt="">';
        }
        return $this->content;
    }

    public function applicable_formats() {
        return array('all' => false, 'course-*' => true);
    }

    public function has_config() {
        return true;
    }

    public function instance_allow_config() {
        return true;
    }

}
