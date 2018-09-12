<?php
/**
 * EVTP Training Plans
 *
 * planitemform form definition.
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
 * Form to add/edit item for the registrar training plan.
 *
 * @package    local_evtp
 * @author     Shane Elliott (@link shane@pukunui.com)
 * @copyright  2018 Pukunui (@link https://pukunui.com/)
 * @license    https://www/gnu.org/copyleft/copyleft/gpl.html GNU GPL v3 or later
 */
class planitemform extends \moodleform {

    /**
     * Form definition.
     *
     * @return void
     */
    public function definition() {
        global $USER;

        $mform =& $this->_form;
        $customdata =& $this->_customdata;

        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'regplanid', 0);
        $mform->setType('regplanid', PARAM_INT);

        $mform->addElement('static', 'planname', get_string('trainingplan', 'local_evtp'));

        $mform->addElement('static', 'registrarname', get_string('registrar', 'local_evtp'));

        $mform->addElement('static', 'mename', get_string('me', 'local_evtp'));

        $mform->addElement('static', 'name', get_string('traininglineitem', 'local_evtp'));

        $mform->addElement('static', 'description', get_string('description', 'local_evtp'));

        $statuses = array(0 => get_string('choosedots', 'local_evtp')) + \local_evtp\utils::get_registraritem_statuses();
        $mform->addElement('select', 'status', get_string('status', 'local_evtp'), $statuses);
        $mform->setType('status', PARAM_INT);

        $stopyear = (int)date("Y") + 10;
        $now = time();
        $dateattr = array('startyear' => 2010, 
                          'stopyear'  => $stopyear,
                          'timezone'  => 99,
                          'optional'  => false
                         );
        $mform->addElement('date_selector', 'startdate', get_string('plannedcommencementdate', 'local_evtp'), $dateattr);
        $mform->setType('startdate', PARAM_INT);
        $mform->setDefault('startdate', $now);

        $mform->addElement('date_selector', 'completiondate', get_string('plannedcompletiondate', 'local_evtp'), $dateattr);
        $mform->setType('completiondate', PARAM_INT);
        $mform->setDefault('completiondate', $now);

        if ($customdata['registrar']) {
            $mform->addElement('editor', 'plannedplacementeditor',
                               get_string('plannedplacement', 'local_evtp'),
                               array('maxfiles' => 0,
                                     'enable_filemanagement' => false));

            $mform->addElement('editor', 'registrarcommentseditor',
                               get_string('registrarcomments', 'local_evtp'),
                               array('maxfiles' => 0,
                                     'enable_filemanagement' => false));
        } else {
            $mform->addElement('static', 'plannedplacement', get_string('plannedplacement', 'local_evtp'));

            $mform->addElement('static', 'registrarcomments', get_string('registrarcomments', 'local_evtp'));
        }

        if ($customdata['me'] or $customdata['admin']) {
            $mform->addElement('editor', 'mecommentseditor',
                               get_string('mecomments', 'local_evtp'),
                               array('maxfiles' => 0,
                                     'enable_filemanagement' => false));
        } else {
            $mform->addElement('static', 'mecomments', get_string('mecomments', 'local_evtp'));
        }

        $this->add_action_buttons(true, get_string('save', 'local_evtp'));
    }

    /**
     * Form validation.
     *
     * @param array $data  data from the form.
     * @param array $files  files uploaded.
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Completion date should be after start date.
        if ($data['completiondate'] < $data['startdate']) {
            $errors['completiondate'] = get_string('errorcompletiondatebeforestart', 'local_evtp');
        }

        return $errors;
    }

}
