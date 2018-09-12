<?php
/**
 * EVTP Training Plans
 *
 * plansearchform form definition.
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
 * Form to search for a training plan.
 *
 * @package    local_evtp
 * @author     Shane Elliott (@link shane@pukunui.com)
 * @copyright  2018 Pukunui (@link https://pukunui.com/)
 * @license    https://www/gnu.org/copyleft/copyleft/gpl.html GNU GPL v3 or later
 */
class plansearchform extends \moodleform {

    /**
     * Form definition.
     *
     * @return void
     */
    public function definition() {
        global $USER;

        $mform =& $this->_form;

        $allarray = array(0 => get_string('all', 'local_evtp'));

        // Search by training plan.
        $plans = \local_evtp\utils::get_training_plans(true, false);
        if (count($plans) > 1) {
            $plans = $allarray + $plans;
        }
        $mform->addElement('select', 'planid', get_string('templateplans', 'local_evtp'), $plans);
        $mform->setType('planid', PARAM_INT);
        $mform->setDefault('planid', 0);

        // Search by ME.
        $mes = \local_evtp\utils::get_mes();
        $strmes = get_string('mes', 'local_evtp');
        if (has_capability('local/evtp:manage', \context_system::instance())) {
            if (count($mes) > 1) {
                $mes = $allarray + $mes;
            }
            $mform->addElement('select', 'meid', $strmes, $mes);
            $mform->setDefault('meid', 0);
        } else if (array_key_exists($USER->id, $mes)) {
            $mform->addElement('static', 'mestatic', $strmes, $mes[$USER->id]);
            $mform->addElement('hidden', 'meid', $USER->id);
        } else {
            die('The form disappeared in a puff of logic');
        }
        $mform->setType('meid', PARAM_INT);

        // Search by Registrar.
        $registrars = \local_evtp\utils::get_registrars();
        if (count($registrars) > 1) {
            $registrars = $allarray + $registrars;
        }
        $mform->addElement('select', 'registrarid', get_string('registrars', 'local_evtp'), $registrars);
        $mform->setType('registrarid', PARAM_INT);
        $mform->setDefault('registrarid', 0);

        // Search by plan status.
        $statuses = \local_evtp\utils::get_registrarplan_statuses();
        if (count($statuses) > 1) {
            $statuses = $allarray + $statuses;
        }
        $mform->addElement('select', 'planstatus', get_string('planstatus', 'local_evtp'), $statuses);
        $mform->setType('planstatus', PARAM_INT);
        $mform->setDefault('planstatus', 0);

        // Search by pathway.
        $pathways = \local_evtp\utils::get_pathways();
        if (count($pathways) > 1) {
            // NB The keys will bepushed up by one after merge.
            $pathways = array_merge($allarray, $pathways);
        }
        $mform->addElement('select', 'pathway', get_string('pathways', 'local_evtp'), $pathways);
        $mform->setType('pathway', PARAM_INT);
        $mform->setDefault('pathway', 0);

        // Search by region.
        $regions = \local_evtp\utils::get_regions();
        if (count($regions) > 1) {
            // NB The keys will bepushed up by one after merge.
            $regions = array_merge($allarray, $regions);
        }
        $mform->addElement('select', 'region', get_string('regions', 'local_evtp'), $regions);
        $mform->setType('region', PARAM_INT);
        $mform->setDefault('region', 0);

        // Search by cohort.
        $cohorts = \local_evtp\utils::get_cohorts();
        if (count($cohorts) > 1) {
            // NB The keys will bepushed up by one after merge.
            $cohorts = array_merge($allarray, $cohorts);
        }
        $mform->addElement('select', 'cohort', get_string('cohorts', 'local_evtp'), $cohorts);
        $mform->setType('cohort', PARAM_INT);
        $mform->setDefault('cohort', 0);

        $this->add_action_buttons(true, get_string('search', 'local_evtp'));
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
