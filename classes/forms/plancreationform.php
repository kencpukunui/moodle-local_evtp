<?php
/**
 * EVTP Training Plans
 *
 * plancreationform form definition.
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
 * Form to create a new registrar training plan.
 *
 * @package    local_evtp
 * @author     Shane Elliott (@link shane@pukunui.com)
 * @copyright  2018 Pukunui (@link https://pukunui.com/)
 * @license    https://www/gnu.org/copyleft/copyleft/gpl.html GNU GPL v3 or later
 */
class plancreationform extends \moodleform {

    /**
     * Form definition.
     *
     * @return void
     */
    public function definition() {
        $mform =& $this->_form;

        $choosearray = array(0 => get_string('choosedots', 'local_evtp'));

        // Search by training plan.
        $plans = \local_evtp\utils::get_training_plans(false, false);
        if (count($plans) > 1) {
            $plans = $choosearray + $plans;
        }
        $mform->addElement('select', 'planid', get_string('trainingplans', 'local_evtp'), $plans);
        $mform->setType('planid', PARAM_INT);
        $mform->setDefault('planid', 0);

        // Search by ME.
        $mes = \local_evtp\utils::get_mes();
        if (count($mes) > 1) {
            $mes = $choosearray + $mes;
        }
        $mform->addElement('select', 'meid', get_string('mes', 'local_evtp'), $mes);
        $mform->setType('meid', PARAM_INT);
        $mform->setDefault('meid', 0);

        // Search by Registrar.
        $registrars = \local_evtp\utils::get_registrars();
        if (count($registrars) > 1) {
            $registrars = $choosearray + $registrars;
        }
        $mform->addElement('select', 'registrarid', get_string('registrars', 'local_evtp'), $registrars);
        $mform->setType('registrarid', PARAM_INT);
        $mform->setDefault('registrarid', 0);

        $this->add_action_buttons(true, get_string('createnewtrainingplan', 'local_evtp'));
    }

    /**
     * Form validation.
     *
     * @param array $data  data from the form.
     * @param array $files  files uploaded.
     * @return array
     */
    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);

        if (empty($data['planid'])) {
            $errors['planid'] = get_string('errormustselectaplan', 'local_evtp');
        }
        if (empty($data['meid'])) {
            $errors['meid'] = get_string('errormustselectame', 'local_evtp');
        }
        if (empty($data['registrarid'])) {
            $errors['registrarid'] = get_string('errormustselectaregistrar', 'local_evtp');
        }

        // Check that the combination doesn't already exist.
        if ($DB->record_exists('local_evtp_regplan', array('planid' => $data['planid'],
                                                            'meuserid' => $data['meid'],
                                                            'registraruserid' => $data['registrarid']))) {
            $errors['planid'] = get_string('errorplanmeregistrarcombination', 'local_evtp');
        }

        return $errors;
    }

}
