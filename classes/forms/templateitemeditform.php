<?php
/**
 * EVTP Training Plans
 *
 * templateitemeditform form definition.
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
 * Form to edit a training plan item.
 *
 * @package    local_evtp
 * @author     Shane Elliott (@link shane@pukunui.com)
 * @copyright  2018 Pukunui (@link https://pukunui.com/)
 * @license    https://www/gnu.org/copyleft/copyleft/gpl.html GNU GPL v3 or later
 */
class templateitemeditform extends \moodleform {

    /**
     * Form definition.
     *
     * @return void
     */
    public function definition() {
        $mform =& $this->_form;
        $customdata = $this->_customdata;

        $mform->addElement('static', 'planname', get_string('trainingplan', 'local_evtp'), $customdata['planname']);

        $mform->addElement('text', 'name', get_string('itemname', 'local_evtp'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        $linetypes = \local_evtp\utils::get_item_statuses();
        $mform->addElement('select', 'linetype', get_string('linetype', 'local_evtp'), $linetypes);
        $mform->setType('linetype', PARAM_INT);
        $mform->setDefault('linetype', \local_evtp\utils::ITEM_MANDATORY);

        $mform->addElement('editor', 'descriptioneditor',
                           get_string('description', 'local_evtp'),
                           array('maxfiles' => 0,
                                 'enable_filemanagement' => false));

        $itemcount = (!isset($customdata['itemcount'])) ? 0 : $customdata['itemcount'];
        $sequenceitems = array();
        for ($i=1; $i <= ($itemcount + 1); $i++) {
            $sequenceitems[$i] = $i;
        }
        $mform->addElement('select', 'sequence', get_string('sequence', 'local_evtp'), $sequenceitems);
        $mform->setType('sequence', PARAM_INT);
        $mform->setDefault('sequence', ($itemcount + 1));

        $mform->addElement('hidden', 'action', 'edit');
        $mform->setType('action', PARAM_ALPHA);

        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'planid', $customdata['planid']);
        $mform->setType('planid', PARAM_INT);

        $mform->addElement('hidden', 'itemcount', $customdata['itemcount']);
        $mform->setType('itemcount', PARAM_INT);

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
        return parent::validation($data, $files);
    }

}
