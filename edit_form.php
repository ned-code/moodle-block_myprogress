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
require_once(dirname(__FILE__) . '/../../config.php');

/**
 * Simple FN_TABS block config form class
 *
 * @copyright 2011 MoodleFN
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_fn_myprogress_edit_form extends block_edit_form {

    protected function specific_definition($mform) {

        // Section header title according to language file.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block_fn_myprogress'));

        $mform->addElement('static', 'blockinfo', get_string('blockinfo', 'block_fn_myprogress'),
            '<a target="_blank" href="http://ned.ca/my-progress">http://ned.ca/my-progress</a>');

        // Config title for the block.
        $mform->addElement('text', 'config_title', get_string('setblocktitle', 'block_fn_myprogress'));
        $mform->setType('config_title', PARAM_TEXT);
        $mform->setDefault('config_title', get_string('blocktitle', 'block_fn_myprogress'));
        $mform->addHelpButton('config_title', 'config_title', 'block_fn_myprogress');

        $yesno = array(0 => get_string('no'), 1 => get_string('yes'));
        $mform->addElement('select', 'config_showdraft',
            get_string('showdraft', 'block_fn_myprogress'), $yesno);
        $mform->setDefault('config_showdraft', 1);
    }
}