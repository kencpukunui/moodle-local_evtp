<?php
/**
 * EVTP Training Plans
 *
 * planstatusform form definition.
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
 * Form to add/edit a status log for the registrar training plan.
 *
 * @package    local_evtp
 * @author     Shane Elliott (@link shane@pukunui.com)
 * @copyright  2018 Pukunui (@link https://pukunui.com/)
 * @license    https://www/gnu.org/copyleft/copyleft/gpl.html GNU GPL v3 or later
 */
class planstatusform extends \moodleform {

    /**
     * Form definition.
     *
     * @return void
     */
    public function definition() {
        global $USER;

        $mform =& $this->_form;
        $strrequired = get_string('required');

        // New status.
        $statuses = \local_evtp\utils::get_registrarplan_statuses();
        $mform->addElement('select', 'status', get_string('status', 'local_evtp'), $statuses);
        $mform->setType('status', PARAM_INT);
        $mform->addRule('status', $strrequired, 'required', null, 'client');

        // Name of current user as editor.
        $mform->addElement('static', 'user', get_string('who', 'local_evtp'), fullname($USER));

        $mform->addElement('hidden', 'userid', $USER->id);
        $mform->setType('userid', PARAM_INT);

        // Comments field.
        $mform->addElement('editor', 'commentseditor',
                            get_string('comments', 'local_evtp'),
                            array('maxfiles' => 0,
                                  'enable_filemanagement' => false));
        $mform->addRule('commentseditor', $strrequired, 'required', null, 'client');

        $mform->addElement('static', 'modifiedtimedisplay', get_string('statusdate', 'local_evtp'), userdate(time(), get_string('strftimedate', 'langconfig')));
        $mform->addElement('hidden', 'modifiedtime', time());
        $mform->setType('modifiedtime', PARAM_INT);

        // Add the registrar training plan id.
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons(true, get_string('addnewstatus', 'local_evtp'));
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
