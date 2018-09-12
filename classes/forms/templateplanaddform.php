<?php
/**
 * EVTP Training Plans
 *
 * templateplanaddform form definition.
 *
 * @package    local_evtp
 * @author     Shane Elliott (@link shane@pukunui.com)
 * @copyright  2018 Pukunui (@link https://pukunui.com/)
 * @license    https://www/gnu.org/copyleft/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evtp\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Form to add a new training plan.
 *
 * @package    local_evtp
 * @author     Shane Elliott (@link shane@pukunui.com)
 * @copyright  2018 Pukunui (@link https://pukunui.com/)
 * @license    https://www/gnu.org/copyleft/copyleft/gpl.html GNU GPL v3 or later
 */
class templateplanaddform extends \moodleform {

    /**
     * Form definition.
     *
     * @return void
     */
    public function definition() {
        $mform =& $this->_form;

        $mform->addElement('text', 'name', get_string('planname', 'local_evtp'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        $mform->addElement('checkbox', 'active', get_string('active', 'local_evtp'));
        
        $thisyear = (int)date("Y");
        $mform->addElement('date_selector', 'startdate', get_string('startdate', 'local_evtp'),
            array('startyear' => $thisyear,
                  'stopyear'  => ($thisyear + 10),
                  'timezone'  => 99,
                  'optional'  => false,
                 ));

        $mform->addElement('hidden', 'action', 'add');
        $mform->setType('action', PARAM_ALPHA);

        $this->add_action_buttons(false, get_string('addnewtrainingplan', 'local_evtp'));
    }

    /**
     * Form validation.
     *
     * @param array $data  data from the form.
     * @param array $files  files uploaded.
     * @return array
     */
    public function validation($data, $files) {
        return parent::validation($data, $files);
    }

}
