<?php

defined('MOODLE_INTERNAL') || die();
require_once(dirname(__FILE__) . '/../../config.php');

/**
 * Simple FN_MY PROGRESS block config form definition
 *
 * @package    contrib
 * @subpackage block_FN MY PROGRESS
 * @copyright  2011 MoodleFN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
        
        //Config title for the block.
        $mform->addElement('text', 'config_title', get_string('setblocktitle', 'block_fn_myprogress'));
        $mform->setType('config_title', PARAM_MULTILANG);
        $mform->setDefault('config_title', get_string('pluginname', 'block_fn_myprogress'));
        $mform->addHelpButton('config_title', 'config_title', 'block_fn_myprogress');       
    }
}